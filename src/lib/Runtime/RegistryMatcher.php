<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;
use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\Contracts\IbexaSites\Site;

/**
 * Tells which siteaccess a request is for from the hosts (and language prefixes) of the active sites, and the other way round for
 * the links between siteaccesses. The first language is at the root of the host, each other one under its prefix (/fr), or has
 * its own host, as the site says.
 *
 * A match is kept in the instance the request goes on with (Ibexa builds one per use), a reverse match in a clone.
 */
final class RegistryMatcher implements VersatileMatcher, URILexer
{
    private static ?SiteSnapshot $snapshot = null;

    private SimplifiedRequest $request;
    /** The prefix of the language of the match ("/fr"), empty at the root. */
    private string $prefix = '';

    /**
     * Ibexa builds a matcher for each use (and keeps the one of the request in its siteaccess), by class name: it cannot be given its
     * dependencies, so the bundle hands it the sites once, at boot. A matcher shared as a service would see the prefix of the
     * request overwritten by the next link built for another siteaccess.
     */
    public static function useSites(?SiteSnapshot $snapshot): void
    {
        self::$snapshot = $snapshot;
    }

    private function sites(): SiteSnapshot
    {
        return self::$snapshot ?? throw new \LogicException('The sites of the registry are not available to the siteaccess matcher.');
    }

    public function setRequest(SimplifiedRequest $request): void
    {
        $this->request = $request;
        $this->prefix = '';
    }

    public function getRequest(): SimplifiedRequest
    {
        return $this->request;
    }

    public function getName(): string
    {
        return 'masilia:sites';
    }

    public function match(): string|bool
    {
        $host = strtolower((string) $this->request->getHost());
        $first = explode('/', ltrim((string) $this->request->getPathInfo(), '/'))[0];
        foreach ($this->sites()->active() as $site) {
            $index = array_search($host, $site->hosts, true);
            if ($index === false) {
                continue;
            }
            $names = array_values($site->siteaccesses());
            if ($site->languageMode === LanguageMode::Host) {
                return $names[$index] ?? $names[0];
            }
            foreach ($site->languages as $position => $language) {
                if ($position > 0 && $first === Site::short($language)) {
                    $this->prefix = '/' . $first;

                    return $names[$position];
                }
            }

            return $names[0];
        }

        return false;
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $found = $this->sites()->bySiteaccess($siteAccessName);
        if ($found === null) {
            return null;
        }
        [$site, $language] = $found;
        $position = (int) array_search($language, $site->languages, true);

        $reverse = clone $this;
        $reverse->request = clone $this->request;
        $reverse->request->setHost($site->languageMode === LanguageMode::Host ? ($site->hosts[$position] ?? $site->hosts[0]) : $site->hosts[0]);
        $reverse->prefix = $site->languageMode === LanguageMode::Prefix && $position > 0 ? '/' . Site::short($language) : '';
        // The path of the request is the one without any prefix: the new one is added to links.
        $reverse->request->setPathinfo($this->analyseURI((string) $this->request->getPathInfo()));

        return $reverse;
    }

    public function analyseURI($uri)
    {
        if ($this->prefix === '') {
            return $uri;
        }
        if ($uri === $this->prefix) {
            return '/';
        }

        return str_starts_with((string) $uri, $this->prefix . '/') ? substr((string) $uri, strlen($this->prefix)) : $uri;
    }

    public function analyseLink($linkUri): string
    {
        if ($this->prefix === '') {
            return (string) $linkUri;
        }
        $joining = $linkUri === '' || $linkUri === '/' ? '' : '/';
        $linkUri = ltrim((string) $linkUri, '/');
        $query = '';
        if (($position = strpos($linkUri, '?')) !== false) {
            $query = substr($linkUri, $position);
            $linkUri = substr($linkUri, 0, $position);
        }

        return $this->prefix . $joining . $linkUri . $query;
    }
}
