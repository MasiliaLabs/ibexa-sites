<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Form;

use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteStatus;

/** What the form of a site holds: what the group shares, and a line for each siteaccess. */
final class SiteData
{
    public string $identifier = '';
    public string $name = '';
    public ?int $rootLocationId = null;
    public ?string $design = null;
    public ?string $base = null;
    public ?string $sessionCookieDomain = null;
    public ?string $adminSiteaccess = null;
    /** @var array<int, SiteAccessData> The form leaves gaps in the keys when a line is removed */
    public array $siteaccesses = [];

    public static function fromSite(Site $site): self
    {
        $data = new self();
        $data->identifier = $site->identifier;
        $data->name = $site->name;
        $data->rootLocationId = $site->rootLocationId;
        $data->design = $site->design;
        $data->base = $site->base;
        $data->sessionCookieDomain = $site->sessionCookieDomain;
        $data->adminSiteaccess = $site->adminSiteaccess;
        $data->siteaccesses = array_map(static fn ($s): SiteAccessData => SiteAccessData::fromDefinition($s), $site->siteaccesses);

        return $data;
    }

    /** The site the form describes: a new one is a draft, a change keeps the status of the site. */
    public function toSite(SiteStatus $status = SiteStatus::Draft): Site
    {
        $blank = static fn (?string $value): ?string => $value === null || trim($value) === '' ? null : trim($value);

        return new Site(
            trim($this->identifier),
            trim($this->name),
            $status,
            (int) $this->rootLocationId,
            (string) $this->design,
            array_values(array_map(static fn (SiteAccessData $s) => $s->toDefinition(), $this->siteaccesses)),
            $blank($this->base),
            $blank($this->sessionCookieDomain),
            $blank($this->adminSiteaccess),
        );
    }
}
