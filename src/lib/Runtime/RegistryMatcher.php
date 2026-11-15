<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\URILexer;
use Ibexa\Core\MVC\Symfony\SiteAccess\VersatileMatcher;

/**
 * Tells which siteaccess a request is for from the hosts (and path prefixes) of the active sites, and the other way round for
 * the links between siteaccesses. The siteaccess without a prefix is at the root of its host, the others under their prefix (/fr).
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
        $bare = null;
        foreach ($this->sites()->active() as $site) {
            foreach ($site->allSiteaccesses() as $siteaccess) {
                if ($siteaccess->host !== $host) {
                    continue;
                }
                if ($siteaccess->uriPrefix === null) {
                    $bare ??= $siteaccess->name;
                } elseif ($siteaccess->uriPrefix === $first) {
                    $this->prefix = '/' . $first;

                    return $siteaccess->name;
                }
            }
        }

        return $bare ?? false;
    }

    public function reverseMatch(string $siteAccessName): ?VersatileMatcher
    {
        $found = $this->sites()->bySiteaccess($siteAccessName);
        if ($found === null) {
            return null;
        }
        $siteaccess = $found[1];

        $reverse = clone $this;
        $reverse->request = clone $this->request;
        $reverse->request->setHost($siteaccess->host);
        $reverse->prefix = $siteaccess->uriPrefix === null ? '' : '/' . $siteaccess->uriPrefix;
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
