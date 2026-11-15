<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Validation;

use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteInvalid;

/**
 * What makes a site acceptable. Every problem is said at once, in words for the person (or the model) who wrote the site.
 *
 * A draft may carry the name, the hosts and the root of what the project still declares in its YAML (that is how a site is taken
 * over from the YAML); only a site that is served must not clash with it.
 */
readonly class SiteValidator
{
    private const IDENTIFIER = '/^[a-z][a-z0-9_]{1,29}$/';
    private const SITEACCESS = '/^[a-z][a-z0-9_]{1,39}$/';
    private const PREFIX = '/^[a-z0-9][a-z0-9-]{0,29}$/';
    private const VAR_DIR = '/^[a-z0-9][a-z0-9_-]*(\/[a-z0-9][a-z0-9_-]*)*$/';
    private const REMOTE_ID = '/^[A-Za-z0-9_-]{1,100}$/';
    private const COOKIE_DOMAIN = '/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/';
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
            ...$this->siteaccesses($site, $others),
            ...$this->session($site, $others),
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
        if ($site->status->isServed() && in_array($site->identifier, $this->facts->reservedNames(), true)) {
            $problems[] = sprintf('"%s" is a siteaccess or a group the project still declares: take it out of the YAML first, or choose another identifier.', $site->identifier);
        }
        if ($isNew && array_filter($others, static fn (Site $o): bool => $o->identifier === $site->identifier) !== []) {
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
    private function siteaccesses(Site $site, array $others): array
    {
        if ($site->siteaccesses === []) {
            return ['A site needs at least one siteaccess.'];
        }
        $problems = [];
        $takenNames = [];
        $takenAddresses = [];
        foreach ($others as $other) {
            foreach ($other->allSiteaccesses() as $s) {
                $takenNames[] = $s->name;
                $takenAddresses[] = $s->host . '/' . ($s->uriPrefix ?? '');
            }
            $takenNames[] = $other->identifier;
        }
        $seenNames = [];
        $seenAddresses = [];
        $bareHosts = [];
        foreach ($site->allSiteaccesses() as $s) {
            $problems = [...$problems, ...$this->siteaccess($site, $s, $takenNames)];
            $address = $s->host . '/' . ($s->uriPrefix ?? '');
            if (in_array($s->name, $seenNames, true)) {
                $problems[] = sprintf('The siteaccess "%s" is given twice.', $s->name);
            }
            if (in_array($address, $seenAddresses, true)) {
                $problems[] = sprintf('Two siteaccesses answer on the same address (%s).', $this->shown($s));
            } elseif (in_array($address, $takenAddresses, true)) {
                $problems[] = sprintf('The address %s is already used by another site.', $this->shown($s));
            }
            if ($s->uriPrefix === null) {
                if (in_array($s->host, $bareHosts, true)) {
                    $problems[] = sprintf('Only one siteaccess can be at the root of the host "%s": give the others a prefix.', $s->host);
                }
                $bareHosts[] = $s->host;
            }
            $seenNames[] = $s->name;
            $seenAddresses[] = $address;
        }

        return $problems;
    }

    /**
     * The cookie of the session of a site: one for the site, its siteaccesses and its admins (the editor is known on the site he
     * works on), and for nobody else. A site with no cookie domain takes the session of its base group, so that group must not have
     * one of its own.
     *
     * @param list<Site> $others
     *
     * @return list<string>
     */
    private function session(Site $site, array $others): array
    {
        if ($site->sessionCookieDomain === null) {
            // Without a cookie of its own the site gets the session of its base group: the one of another site, if the YAML gives it one.
            return $site->base !== null && $this->facts->groupHasSession($site->base)
                ? [sprintf('The group "%s" has a session of its own (the cookie of another site): give this site its own cookie domain, or take another base group.', $site->base)]
                : [];
        }
        $domain = $site->sessionCookieDomain;
        $bare = ltrim($domain, '.');
        if ($domain !== strtolower($domain) || preg_match(self::COOKIE_DOMAIN, $bare) !== 1) {
            return [sprintf('"%s" is not a cookie domain (lower case, at least two labels, like ".example.org").', $domain)];
        }
        $covers = static fn (string $host): bool => $host === $bare || str_ends_with($host, '.' . $bare);
        $problems = [];
        foreach ($site->hosts() as $host) {
            if (!$covers($host)) {
                $problems[] = sprintf('The cookie domain "%s" does not cover the host "%s" of the site: the session would not be shared by its siteaccesses.', $domain, $host);
            }
        }
        $foreign = $this->facts->staticHosts();
        foreach ($others as $other) {
            $foreign = [...$foreign, ...$other->hosts()];
        }
        foreach (array_unique($foreign) as $host) {
            if ($covers($host)) {
                $problems[] = sprintf('The cookie domain "%s" would also be sent to "%s", which belongs to another site: a user logged in here would be known there. Use a narrower domain.', $domain, $host);
            }
        }

        return $problems;
    }

    /**
     * @param list<string> $takenNames
     *
     * @return list<string>
     */
    private function siteaccess(Site $site, SiteAccessDefinition $s, array $takenNames): array
    {
        $problems = [];
        if (preg_match(self::SITEACCESS, $s->name) !== 1) {
            $problems[] = sprintf('The siteaccess name "%s" is not valid: 2 to 40 characters, lower case letters, digits and "_", starting with a letter.', $s->name);
        }
        if ($s->name === $site->identifier || in_array($s->name, $takenNames, true)) {
            $problems[] = sprintf('The siteaccess name "%s" is already used by another site.', $s->name);
        }
        if ($site->status->isServed() && in_array($s->name, $this->facts->reservedNames(), true)) {
            $problems[] = sprintf('The siteaccess "%s" still exists in the project\'s YAML: take it out first.', $s->name);
        }
        if ($s->host !== strtolower($s->host) || preg_match(self::HOST, $s->host) !== 1) {
            $problems[] = sprintf('"%s" is not a host name (lower case, like "demo.example.org").', $s->host);
        } elseif ($site->status->isServed() && in_array($s->host, $this->facts->staticHosts(), true)) {
            $problems[] = sprintf('The host "%s" is still answered by the project\'s YAML: take it out first.', $s->host);
        }
        if ($s->uriPrefix !== null && preg_match(self::PREFIX, $s->uriPrefix) !== 1) {
            $problems[] = sprintf('The prefix "%s" is not valid: lower case letters, digits and "-", without slash.', $s->uriPrefix);
        }
        if ($s->languages === []) {
            $problems[] = sprintf('The siteaccess "%s" needs at least one language.', $s->name);
        }
        if (count($s->languages) !== count(array_unique($s->languages))) {
            $problems[] = sprintf('A language is given twice for the siteaccess "%s".', $s->name);
        }
        foreach ($s->languages as $code) {
            if (!$this->facts->languageExists($code)) {
                $problems[] = sprintf('The language "%s" does not exist in Ibexa: add it to the installation first.', $code);
            }
        }

        return $problems;
    }

    private function shown(SiteAccessDefinition $s): string
    {
        return $s->host . ($s->uriPrefix === null ? '' : '/' . $s->uriPrefix);
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
        } elseif (array_filter($others, static fn (Site $o): bool => $o->rootLocationId === $site->rootLocationId) !== []) {
            $problems[] = sprintf('The location %d is already the root of another site.', $site->rootLocationId);
        } elseif ($site->status->isServed() && in_array($site->rootLocationId, $this->facts->staticRoots(), true)) {
            $problems[] = sprintf('The location %d is still the root of a site of the project\'s YAML.', $site->rootLocationId);
        }
        if (!in_array($site->design, $this->facts->designs(), true)) {
            $problems[] = sprintf('The design "%s" is not declared. Available designs: %s.', $site->design, implode(', ', $this->facts->designs()));
        }
        if ($site->base !== null && !in_array($site->base, $this->facts->baseGroups(), true)) {
            $problems[] = sprintf('"%s" is not a group a site can take its settings from. Available: %s.', $site->base, implode(', ', $this->facts->baseGroups()));
        }
        if ($site->adminSiteaccess !== null && !in_array($site->adminSiteaccess, $this->facts->adminSiteaccesses(), true)) {
            $problems[] = sprintf('"%s" is not an admin siteaccess of the project. Available: %s.', $site->adminSiteaccess, implode(', ', $this->facts->adminSiteaccesses()));
        }

        if ($site->varDir !== null && preg_match(self::VAR_DIR, $site->varDir) !== 1) {
            $problems[] = sprintf('The directory "%s" is not valid: a relative path of lower case letters, digits, "-" and "_" ("var/demo").', $site->varDir);
        }
        if ($site->layoutRemoteId !== null) {
            if (preg_match(self::REMOTE_ID, $site->layoutRemoteId) !== 1) {
                $problems[] = sprintf('The layout remote id "%s" is not valid.', $site->layoutRemoteId);
            } elseif (!$this->facts->contentExists($site->layoutRemoteId)) {
                $problems[] = sprintf('No content has the remote id "%s": create the layout of the site first.', $site->layoutRemoteId);
            }
        }

        return $problems;
    }
}
