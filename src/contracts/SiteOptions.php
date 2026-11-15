<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/** What a site can be set to here: the choices a form (or an assistant) offers, as the project and Ibexa have them. */
interface SiteOptions
{
    /** @return list<string> The language codes Ibexa has */
    public function languages(): array;

    /** @return list<string> The designs the project declares */
    public function designs(): array;

    /** @return list<string> The groups a site can take its settings from */
    public function baseGroups(): array;

    /** @return list<string> The admin siteaccesses of the project's YAML (a site can be linked to one) */
    public function adminSiteaccesses(): array;
}
