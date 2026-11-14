<?php

declare(strict_types=1);

namespace Masilia\Bundle\IbexaSites\Form;

use Masilia\Contracts\IbexaSites\SiteAccessDefinition;

/** What the form of one siteaccess holds: its language, the ones it falls back to, its address. */
final class SiteAccessData
{
    public string $name = '';
    public ?string $language = null;
    /** @var list<string> */
    public array $fallbacks = [];
    public string $host = '';
    public ?string $uriPrefix = null;

    public static function fromDefinition(SiteAccessDefinition $definition): self
    {
        $data = new self();
        $data->name = $definition->name;
        $data->language = $definition->languages[0] ?? null;
        $data->fallbacks = array_slice($definition->languages, 1);
        $data->host = $definition->host;
        $data->uriPrefix = $definition->uriPrefix;

        return $data;
    }

    public function toDefinition(): SiteAccessDefinition
    {
        $languages = $this->language === null || $this->language === '' ? [] : [$this->language, ...array_values(array_diff($this->fallbacks, [$this->language]))];
        $prefix = trim((string) $this->uriPrefix, " /\t");

        return new SiteAccessDefinition(trim($this->name), $languages, strtolower(trim($this->host)), $prefix === '' ? null : strtolower($prefix));
    }
}
