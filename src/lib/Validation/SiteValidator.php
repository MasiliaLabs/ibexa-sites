<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Validation;

use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteStatus;

/**
 * What makes a site acceptable. Every problem is said at once, in words for the person (or the model) who wrote the site.
 */
readonly class SiteValidator
{
    private const IDENTIFIER = '/^[a-z][a-z0-9_]{1,29}$/';
    private const HOST = '/^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/';

    public function __construct(private SiteFacts $facts)
    {
    }

    /**
     * @param list<Site> $others The sites of the registry other than this one
     *
     * @throws SiteInvalid
     */
    public function validate(Site $site, array $others, bool $isNew): void
    {
        $problems = [
            ...$this->identity($site, $others, $isNew),
            ...$this->addresses($site, $others),
            ...$this->languages($site),
            ...$this->settings($site, $others),
        ];
        if ($problems !== []) {
            throw new SiteInvalid($problems);
        }
    }

    /**
     * @param list<Site> $others
     *
     * @return list<string>
     */
    private function identity(Site $site, array $others, bool $isNew): array
    {
        $problems = [];
        if (preg_match(self::IDENTIFIER, $site->identifier) !== 1) {
            $problems[] = sprintf('The identifier "%s" is not valid: 2 to 30 characters, lower case letters, digits and "_", starting with a letter.', $site->identifier);
        }
        if ($isNew && in_array($site->identifier, $this->facts->reservedNames(), true)) {
            $problems[] = sprintf('"%s" is a siteaccess or a group of the project: choose another identifier.', $site->identifier);
        }
        foreach ($site->siteaccesses() as $siteaccess) {
            if ($siteaccess !== $site->identifier && in_array($siteaccess, $this->facts->reservedNames(), true)) {
                $problems[] = sprintf('The siteaccess "%s" that this site would have already exists in the project.', $siteaccess);
            }
        }
        if ($isNew && $this->identifiers($others, $site->identifier) !== []) {
            $problems[] = sprintf('A site "%s" already exists.', $site->identifier);
        }
        if (trim($site->name) === '' || mb_strlen($site->name) > 100) {
            $problems[] = 'The name is required, 100 characters at most.';
        }

        return $problems;
    }

    /**
     * @param list<Site> $others
     *
     * @return list<string>
     */
    private function addresses(Site $site, array $others): array
    {
        $problems = [];
        if ($site->hosts === []) {
            $problems[] = 'A site needs at least one host.';
        }
        if ($site->languageMode === LanguageMode::Prefix && count($site->hosts) !== 1) {
            $problems[] = 'With the languages told apart by a prefix (/fr), the site has exactly one host.';
        }
        if ($site->languageMode === LanguageMode::Host && count($site->hosts) !== count($site->languages)) {
            $problems[] = sprintf('With one host per language, give %d hosts (one per language, in the order of the languages), not %d.', count($site->languages), count($site->hosts));
        }
        $taken = $this->facts->staticHosts();
        foreach ($others as $other) {
            $taken = [...$taken, ...$other->hosts];
        }
        foreach ($site->hosts as $host) {
            if ($host !== strtolower($host) || preg_match(self::HOST, $host) !== 1) {
                $problems[] = sprintf('"%s" is not a host name (lower case, like "demo.example.org").', $host);
            } elseif (in_array($host, $taken, true)) {
                $problems[] = sprintf('The host "%s" is already used by another site or by the project.', $host);
            }
        }
        if (count($site->hosts) !== count(array_unique($site->hosts))) {
            $problems[] = 'A host is given twice.';
        }

        return $problems;
    }

    /** @return list<string> */
    private function languages(Site $site): array
    {
        $problems = [];
        if ($site->languages === []) {
            $problems[] = 'A site needs at least one language.';
        }
        if (count($site->languages) !== count(array_unique($site->languages))) {
            $problems[] = 'A language is given twice.';
        }
        foreach ($site->languages as $code) {
            if (!$this->facts->languageExists($code)) {
                $problems[] = sprintf('The language "%s" does not exist in Ibexa: add it to the installation first.', $code);
            }
        }

        return $problems;
    }

    /**
     * @param list<Site> $others
     *
     * @return list<string>
     */
    private function settings(Site $site, array $others): array
    {
        $problems = [];
        if (!$this->facts->locationExists($site->rootLocationId)) {
            $problems[] = sprintf('The root location %d does not exist.', $site->rootLocationId);
        } elseif (in_array($site->rootLocationId, $this->facts->staticRoots(), true) || $this->rootUsedBy($others, $site->rootLocationId)) {
            $problems[] = sprintf('The location %d is already the root of another site.', $site->rootLocationId);
        }
        if (!in_array($site->design, $this->facts->designs(), true)) {
            $problems[] = sprintf('The design "%s" is not declared. Available designs: %s.', $site->design, implode(', ', $this->facts->designs()));
        }
        if (!in_array($site->base, $this->facts->baseGroups(), true)) {
            $problems[] = sprintf('"%s" is not a group a site can take its settings from. Available: %s.', $site->base, implode(', ', $this->facts->baseGroups()));
        }
        if ($site->adminSiteaccess !== null && !in_array($site->adminSiteaccess, $this->facts->adminSiteaccesses(), true)) {
            $problems[] = sprintf('"%s" is not an admin siteaccess of the project. Available: %s.', $site->adminSiteaccess, implode(', ', $this->facts->adminSiteaccesses()));
        }
        if ($site->status === SiteStatus::Active && $site->hosts === []) {
            $problems[] = 'A site without a host cannot be active.';
        }

        return $problems;
    }

    /**
     * @param list<Site> $others
     *
     * @return list<string>
     */
    private function identifiers(array $others, string $identifier): array
    {
        return array_values(array_filter(array_map(static fn (Site $s): string => $s->identifier, $others), static fn (string $i): bool => $i === $identifier));
    }

    /** @param list<Site> $others */
    private function rootUsedBy(array $others, int $root): bool
    {
        foreach ($others as $other) {
            if ($other->rootLocationId === $root) {
                return true;
            }
        }

        return false;
    }
}
