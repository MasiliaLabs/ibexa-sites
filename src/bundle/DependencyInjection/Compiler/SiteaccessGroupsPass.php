<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\DependencyInjection\Compiler;

use Masilia\IbexaSites\Runtime\SiteGroups;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Ibexa's admin and core services receive the siteaccess groups as tables compiled from the YAML ("%ibexa.site_access.groups%" and
 * "%ibexa.site_access.groups_by_site_access%"): they would not know the siteaccesses of the registry (an admin one is refused with
 * "route not allowed in the current SiteAccess"). Wherever a service takes one of the two, it gets the table with them added.
 */
final class SiteaccessGroupsPass implements CompilerPassInterface
{
    /** The parameter that is replaced => the service that holds the completed table, and the method of SiteGroups that builds it. */
    private const TABLES = [
        '%ibexa.site_access.groups%' => ['masilia_ibexa_sites.groups', 'groups'],
        '%ibexa.site_access.groups_by_site_access%' => ['masilia_ibexa_sites.groups_by_siteaccess', 'bySiteaccess'],
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::TABLES as $parameter => [$id, $method]) {
            $container->setDefinition($id, (new Definition('array'))
                ->setFactory([SiteGroups::class, $method])
                ->setArguments([$parameter, new Reference(SiteSnapshot::class), '%admin_group_name%'])
                ->setShared(false));
        }
        $own = array_column(self::TABLES, 0);
        foreach ($container->getDefinitions() as $id => $definition) {
            if (in_array($id, $own, true)) {
                continue;
            }
            $definition->setArguments($this->replace($definition->getArguments()));
            $definition->setMethodCalls(array_map(fn (array $call): array => [$call[0], $this->replace($call[1]), ...array_slice($call, 2)], $definition->getMethodCalls()));
        }
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return array<mixed>
     */
    private function replace(array $arguments): array
    {
        foreach ($arguments as $key => $argument) {
            if (is_string($argument) && isset(self::TABLES[$argument])) {
                $arguments[$key] = new Reference(self::TABLES[$argument][0]);
            } elseif (is_array($argument)) {
                $arguments[$key] = $this->replace($argument);
            }
        }

        return $arguments;
    }
}
