<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Runtime\RegistryMatcher;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** Three sites (a trait of the tests that need them): "demo" (prefix, en/fr), "blog" (a host per language, en/ar), "off" (disabled). */
trait SitesFixture
{
    /** @return list<Site> */
    private static function sites(): array
    {
        return [
            new Site('blog', 'Blog', SiteStatus::Active, ['blog.example.org', 'blog-ar.example.org'], ['eng-GB', 'ara-MA'], LanguageMode::Host, 600, 'imal', 'site_group'),
            new Site('demo', 'Demo', SiteStatus::Active, ['demo.example.org'], ['eng-GB', 'fre-FR'], LanguageMode::Prefix, 500, 'mattcch', 'mattcch_group'),
            new Site('off', 'Off', SiteStatus::Disabled, ['off.example.org'], ['eng-GB'], LanguageMode::Prefix, 700, 'imal', 'site_group'),
        ];
    }

    private function snapshot(bool $matcher = false): SiteSnapshot
    {
        $reader = $this->createMock(SiteReader::class);
        $reader->method('all')->willReturn(self::sites());
        $snapshot = new SiteSnapshot($reader, new ArrayAdapter());
        if ($matcher) {
            RegistryMatcher::useSites($snapshot);
        }

        return $snapshot;
    }
}
