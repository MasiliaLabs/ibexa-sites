<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Runtime;

use Masilia\IbexaSites\Persistence\SettingReader;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * The siteaccess that answers when no host matches: the one chosen in the registry when it is still there, the project's own
 * ("default_siteaccess" of the YAML) otherwise. Read once per request from a cache, and never failing.
 */
final class DefaultSiteaccess
{
    public const SETTING = 'default_siteaccess';
    private const KEY = 'masilia_default_siteaccess';

    private ?string $stored = null;
    private bool $loaded = false;

    /** @param list<string> $static ibexa.site_access.list */
    public function __construct(
        private readonly SettingReader $settings,
        private readonly SiteSnapshot $sites,
        private readonly CacheItemPoolInterface $cache,
        #[Autowire('%ibexa.site_access.list%')]
        private readonly array $static,
    ) {
    }

    /** The name Ibexa uses as its default: the chosen one if it still exists, else the one the YAML gives. */
    public function name(string $compiled): string
    {
        $stored = $this->stored();

        return $stored !== null && (in_array($stored, $this->static, true) || $this->sites->bySiteaccess($stored) !== null) ? $stored : $compiled;
    }

    public function stored(): ?string
    {
        if ($this->loaded) {
            return $this->stored;
        }
        $this->loaded = true;
        try {
            $item = $this->cache->getItem(self::KEY);
            if (!$item->isHit()) {
                $this->cache->save($item->set($this->settings->value(self::SETTING) ?? ''));
            }
            $value = (string) $item->get();

            return $this->stored = $value === '' ? null : $value;
        } catch (Throwable) {
            return $this->stored = null;
        }
    }

    public function invalidate(): void
    {
        $this->loaded = false;
        try {
            $this->cache->deleteItem(self::KEY);
        } catch (Throwable) {
            // Nothing cached to forget.
        }
    }
}
