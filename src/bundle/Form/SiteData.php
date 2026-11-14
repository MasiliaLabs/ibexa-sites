<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Form;

use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteStatus;

/** What the form of a site holds: the hosts as lines of text, the languages as a choice and a default. */
final class SiteData
{
    public string $identifier = '';
    public string $name = '';
    public string $hosts = '';
    /** @var list<string> */
    public array $languages = [];
    public ?string $defaultLanguage = null;
    public string $languageMode = 'prefix';
    public ?int $rootLocationId = null;
    public ?string $design = null;
    public ?string $base = null;
    public ?string $adminSiteaccess = null;

    public static function fromSite(Site $site): self
    {
        $data = new self();
        $data->identifier = $site->identifier;
        $data->name = $site->name;
        $data->hosts = implode("\n", $site->hosts);
        $data->languages = $site->languages;
        $data->defaultLanguage = $site->languages[0] ?? null;
        $data->languageMode = $site->languageMode->value;
        $data->rootLocationId = $site->rootLocationId;
        $data->design = $site->design;
        $data->base = $site->base;
        $data->adminSiteaccess = $site->adminSiteaccess;

        return $data;
    }

    /** The site the form describes: a new one is a draft, a change keeps the status of the site. */
    public function toSite(SiteStatus $status = SiteStatus::Draft): Site
    {
        $languages = $this->languages;
        if ($this->defaultLanguage !== null && in_array($this->defaultLanguage, $languages, true)) {
            $languages = [$this->defaultLanguage, ...array_values(array_diff($languages, [$this->defaultLanguage]))];
        }
        $hosts = array_values(array_filter(array_map(static fn (string $line): string => strtolower(trim($line)), preg_split('/[\s,;]+/', $this->hosts) ?: [])));

        return new Site(
            trim($this->identifier),
            trim($this->name),
            $status,
            $hosts,
            $languages,
            LanguageMode::tryFrom($this->languageMode) ?? LanguageMode::Prefix,
            (int) $this->rootLocationId,
            (string) $this->design,
            (string) $this->base,
            $this->adminSiteaccess !== null && $this->adminSiteaccess !== '' ? $this->adminSiteaccess : null,
        );
    }
}
