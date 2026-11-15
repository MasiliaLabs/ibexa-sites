<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Validation;

/** What the validation asks Ibexa and the project about: the facts a site must agree with. */
interface SiteFacts
{
    public function locationExists(int $locationId): bool;

    /** Whether a content with this remote id exists (the layout of a site is one) */
    public function contentExists(string $remoteId): bool;

    public function languageExists(string $code): bool;

    /** @return list<string> The language codes Ibexa has, enabled ones first */
    public function languages(): array;

    /** @return list<string> The designs already declared */
    public function designs(): array;

    /** @return list<string> The siteaccess groups a site can take its settings from (the front ones, not the admin) */
    public function baseGroups(): array;

    /** @return list<string> The admin siteaccesses that exist */
    public function adminSiteaccesses(): array;

    /** @return list<string> The names of the siteaccesses and groups the project declares: a site cannot take them */
    public function reservedNames(): array;

    /** @return list<string> The hosts the project already answers on (the fronts and the admins) */
    public function staticHosts(): array;

    /** @return list<int> The root locations the project's sites already use */
    public function staticRoots(): array;
}
