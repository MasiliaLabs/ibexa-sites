<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/**
 * The settings that belong to the whole platform and not to one site. For now the siteaccess that answers when no host matches
 * (Ibexa's "default_siteaccess"): what the project's YAML says until a siteaccess is chosen here.
 */
interface SiteSettings
{
    /** @return string|null The siteaccess chosen, or null when the YAML's default is the one used */
    public function defaultSiteaccess(): ?string;

    /** @return list<string> The siteaccesses that can be chosen: the project's fronts and those of the served sites */
    public function defaultSiteaccessChoices(): array;

    /**
     * @param string|null $siteaccess A siteaccess of the project's YAML or of a served site; null goes back to the YAML's
     *
     * @throws SiteInvalid
     */
    public function setDefaultSiteaccess(?string $siteaccess): void;
}
