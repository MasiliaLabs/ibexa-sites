<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites;

use Masilia\Bundle\IbexaSites\DependencyInjection\Compiler\CompiledSiteaccessPass;
use Masilia\Bundle\IbexaSites\DependencyInjection\Compiler\TranslationHelperPass;
use Masilia\Bundle\IbexaSites\Security\SitesPolicyProvider;
use Masilia\IbexaSites\Runtime\RegistryMatcher;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** Sites created at run time: the registry, and what lets Ibexa serve them without a deploy. */
final class MasiliaIbexaSitesBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // The "masilia_site" permissions: who may see the sites, who may change them.
        /** @var \Ibexa\Bundle\Core\DependencyInjection\IbexaCoreExtension $core */
        $core = $container->getExtension('ibexa');
        $core->addPolicyProvider(new SitesPolicyProvider());
        $container->addCompilerPass(new TranslationHelperPass());
        // Twice: once for what the YAML of services says, once after autowiring, which resolves #[Autowire('%ibexa.site_access.groups%…')]
        // arguments late (a service that asks for them that way, like masilia/ibexa-ai's SiteUrls, would else get the YAML alone).
        $container->addCompilerPass(new CompiledSiteaccessPass());
        $container->addCompilerPass(new CompiledSiteaccessPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    public function boot(): void
    {
        parent::boot();

        // Ibexa builds the siteaccess matcher by class name: it is given the sites here (see RegistryMatcher::useSites()).
        if ($this->container !== null && $this->container->has(SiteSnapshot::class)) {
            RegistryMatcher::useSites($this->container->get(SiteSnapshot::class));
        }
    }

    public function shutdown(): void
    {
        RegistryMatcher::useSites(null);
        parent::shutdown();
    }
}
