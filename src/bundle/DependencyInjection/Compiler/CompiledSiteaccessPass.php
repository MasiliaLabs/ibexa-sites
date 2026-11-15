<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\DependencyInjection\Compiler;

use Masilia\IbexaSites\Runtime\DefaultSiteaccess;
use Masilia\IbexaSites\Runtime\SiteGroups;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Ibexa's services receive what the YAML says about siteaccesses compiled: the groups ("%ibexa.site_access.groups%" and
 * "%ibexa.site_access.groups_by_site_access%": an admin siteaccess of the registry is refused with "route not allowed in the current
 * SiteAccess" without them added) and the default siteaccess ("%ibexa.site_access.default%": the router, the request listener and the
 * console). Wherever a service takes one of them, it gets the value with the registry taken into account.
 */
final class CompiledSiteaccessPass implements CompilerPassInterface
{
    private const GROUPS = '%ibexa.site_access.groups%';
    private const GROUPS_BY_SITEACCESS = '%ibexa.site_access.groups_by_site_access%';
    private const DEFAULT = '%ibexa.site_access.default%';

    /** @var array<string, string> The parameter that is replaced => the service that holds the completed value */
    private const SERVICES = [
        self::GROUPS => 'masilia_ibexa_sites.groups',
        self::GROUPS_BY_SITEACCESS => 'masilia_ibexa_sites.groups_by_siteaccess',
        self::DEFAULT => 'masilia_ibexa_sites.default_siteaccess',
    ];

    public function process(ContainerBuilder $container): void
    {
        $snapshot = new Reference(SiteSnapshot::class);
        $container->setDefinition(self::SERVICES[self::GROUPS], (new Definition('array'))
            ->setFactory([SiteGroups::class, 'groups'])->setArguments([self::GROUPS, $snapshot, '%admin_group_name%'])->setShared(false));
        $container->setDefinition(self::SERVICES[self::GROUPS_BY_SITEACCESS], (new Definition('array'))
            ->setFactory([SiteGroups::class, 'bySiteaccess'])->setArguments([self::GROUPS_BY_SITEACCESS, $snapshot, '%admin_group_name%'])->setShared(false));
        $container->setDefinition(self::SERVICES[self::DEFAULT], (new Definition('string'))
            ->setFactory([new Reference(DefaultSiteaccess::class), 'name'])->setArguments([self::DEFAULT])->setShared(false));

        // After autowiring, an argument that asked for a parameter through #[Autowire('%…%')] already holds its value: the compiled groups of
        // the YAML, which are recognised by what they are (a group list is never the value of anything else).
        $this->resolved = [];
        foreach ([self::GROUPS, self::GROUPS_BY_SITEACCESS] as $parameter) {
            $name = trim($parameter, '%');
            if ($container->hasParameter($name) && is_array($container->getParameter($name)) && $container->getParameter($name) !== []) {
                $this->resolved[$parameter] = $container->getParameter($name);
            }
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            if (in_array($id, self::SERVICES, true)) {
                continue;
            }
            $definition->setArguments($this->replace($definition->getArguments()));
            $definition->setMethodCalls(array_map(fn (array $call): array => [$call[0], $this->replace($call[1]), ...array_slice($call, 2)], $definition->getMethodCalls()));
        }
    }

    /** @var array<string, array<mixed>> The parameter => its value in the YAML, to recognise it once resolved */
    private array $resolved = [];

    /**
     * @param array<mixed> $arguments
     *
     * @return array<mixed>
     */
    private function replace(array $arguments): array
    {
        foreach ($arguments as $key => $argument) {
            if (is_string($argument) && isset(self::SERVICES[$argument])) {
                $arguments[$key] = new Reference(self::SERVICES[$argument]);
            } elseif (is_array($argument) && ($parameter = array_search($argument, $this->resolved, true)) !== false) {
                $arguments[$key] = new Reference(self::SERVICES[$parameter]);
            } elseif (is_array($argument)) {
                $arguments[$key] = $this->replace($argument);
            }
        }

        return $arguments;
    }
}
