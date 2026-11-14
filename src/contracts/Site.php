<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/**
 * A site as the registry keeps it. Ibexa's own word for what serves it is a siteaccess: a site has one per language, named
 * after the identifier (the first language is the identifier itself, the others carry their code: "demo", "demo_fr").
 */
final readonly class Site
{
    /**
     * @param list<string> $hosts     Host names, lower case: one for the whole site (Prefix), one per language (Host)
     * @param list<string> $languages Ibexa language codes ("eng-GB"), the first is the default
     * @param string       $design    A design already declared (ibexa_design_engine.design_list)
     * @param string       $base      The siteaccess group the site takes the settings it does not set from ("site_group")
     */
    public function __construct(
        public string $identifier,
        public string $name,
        public SiteStatus $status,
        public array $hosts,
        public array $languages,
        public LanguageMode $languageMode,
        public int $rootLocationId,
        public string $design,
        public string $base,
        public ?string $adminSiteaccess = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier, 'name' => $this->name, 'status' => $this->status->value, 'hosts' => $this->hosts, 'languages' => $this->languages,
            'languageMode' => $this->languageMode->value, 'rootLocationId' => $this->rootLocationId, 'design' => $this->design, 'base' => $this->base,
            'adminSiteaccess' => $this->adminSiteaccess,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['identifier'], (string) $data['name'], SiteStatus::from((string) $data['status']), array_map('strval', (array) $data['hosts']),
            array_map('strval', (array) $data['languages']), LanguageMode::from((string) $data['languageMode']), (int) $data['rootLocationId'],
            (string) $data['design'], (string) $data['base'], isset($data['adminSiteaccess']) ? (string) $data['adminSiteaccess'] : null,
        );
    }

    public function withStatus(SiteStatus $status): self
    {
        return new self($this->identifier, $this->name, $status, $this->hosts, $this->languages, $this->languageMode, $this->rootLocationId, $this->design, $this->base, $this->adminSiteaccess);
    }

    /**
     * The siteaccess of each language: "demo" for the first, "demo_fr" for French, as the project names its own.
     *
     * @return array<string, string> Language code => siteaccess name
     */
    public function siteaccesses(): array
    {
        $names = [];
        foreach ($this->languages as $index => $language) {
            $names[$language] = $index === 0 ? $this->identifier : $this->identifier . '_' . self::short($language);
        }

        return $names;
    }

    /**
     * The short code of a language ("fre-FR" is "fr"): what the address of a language carries (/fr) and what its siteaccess is
     * named after. The languages Ibexa writes with three letters are mapped; any other keeps its three letters.
     */
    public static function short(string $language): string
    {
        $three = strtolower(explode('-', $language)[0]);

        return self::SHORT[$three] ?? $three;
    }

    private const SHORT = [
        'eng' => 'en', 'fre' => 'fr', 'ara' => 'ar', 'ger' => 'de', 'spa' => 'es', 'ita' => 'it', 'por' => 'pt', 'dut' => 'nl',
        'rus' => 'ru', 'chi' => 'zh', 'jpn' => 'ja', 'tur' => 'tr', 'pol' => 'pl', 'swe' => 'sv', 'dan' => 'da', 'nor' => 'no',
        'fin' => 'fi', 'gre' => 'el', 'heb' => 'he', 'hin' => 'hi', 'kor' => 'ko', 'cze' => 'cs', 'hun' => 'hu', 'rum' => 'ro',
    ];
}
