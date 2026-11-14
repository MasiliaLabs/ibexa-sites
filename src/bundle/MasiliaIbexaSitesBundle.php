<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites;

use Masilia\IbexaSites\Runtime\RegistryMatcher;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/** Sites created at run time: the registry, and what lets Ibexa serve them without a deploy. */
final class MasiliaIbexaSitesBundle extends Bundle
{
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
