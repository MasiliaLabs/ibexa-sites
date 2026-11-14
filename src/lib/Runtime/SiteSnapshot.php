<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\IbexaSites\Persistence\SiteReader;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;

/**
 * The sites that are served, as the run time reads them: once per request from a cache, the database only when the cache is
 * empty or after a write. It never fails: before the table exists (a migration, a cache warm-up) or when the database is not
 * reachable there are simply no sites, and the project's own siteaccesses answer as always.
 */
final class SiteSnapshot
{
    private const KEY = 'masilia_sites_active';

    /** @var list<Site>|null */
    private ?array $active = null;

    public function __construct(private readonly SiteReader $reader, private readonly CacheItemPoolInterface $cache)
    {
    }

    /** @return list<Site> */
    public function active(): array
    {
        if ($this->active !== null) {
            return $this->active;
        }
        try {
            $item = $this->cache->getItem(self::KEY);
            if ($item->isHit() && is_array($item->get())) {
                return $this->active = array_map(static fn (array $row): Site => Site::fromArray($row), array_values($item->get()));
            }
            $active = array_values(array_filter($this->reader->all(), static fn (Site $site): bool => $site->status->isServed()));
            $this->cache->save($item->set(array_map(static fn (Site $site): array => $site->toArray(), $active)));

            return $this->active = $active;
        } catch (Throwable) {
            return $this->active = [];
        }
    }

    public function invalidate(): void
    {
        $this->active = null;
        try {
            $this->cache->deleteItem(self::KEY);
        } catch (Throwable) {
            // Nothing cached to forget.
        }
    }

    /**
     * The site that has this siteaccess, and the siteaccess itself.
     *
     * @return array{0: Site, 1: SiteAccessDefinition}|null
     */
    public function bySiteaccess(string $siteaccess): ?array
    {
        foreach ($this->active() as $site) {
            if (($definition = $site->siteaccess($siteaccess)) !== null) {
                return [$site, $definition];
            }
        }

        return null;
    }
}
