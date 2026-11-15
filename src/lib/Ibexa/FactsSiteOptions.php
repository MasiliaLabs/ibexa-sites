<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Ibexa;

use Masilia\Contracts\IbexaSites\SiteOptions;
use Masilia\IbexaSites\Validation\SiteFacts;

/** The options of a site, read from the same facts the validation uses: what is offered is what is accepted. */
readonly class FactsSiteOptions implements SiteOptions
{
    public function __construct(private SiteFacts $facts)
    {
    }

    public function languages(): array
    {
        return $this->facts->languages();
    }

    public function designs(): array
    {
        return $this->facts->designs();
    }

    public function baseGroups(): array
    {
        return $this->facts->baseGroups();
    }

    public function adminSiteaccesses(): array
    {
        return $this->facts->adminSiteaccesses();
    }
}
