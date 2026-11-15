<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Runtime\RegistryMatcher;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** Three sites (a trait of the tests that need them): "demo" (en at the root, fr under /fr with an english fallback), "blog" (a host per language, en/ar), "off" (disabled). */
trait SitesFixture
{
    /** @return list<Site> */
    private static function sites(): array
    {
        return [
            new Site('blog_group', 'Blog', SiteStatus::Active, 600, 'imal', [
                new SiteAccessDefinition('blog', ['eng-GB'], 'blog.example.org'),
                new SiteAccessDefinition('blog_ar', ['ara-MA'], 'blog-ar.example.org'),
            ], 'site_group'),
            new Site('demo_group', 'Demo', SiteStatus::Active, 500, 'mattcch', [
                new SiteAccessDefinition('demo', ['eng-GB'], 'demo.example.org'),
                new SiteAccessDefinition('demo_fr', ['fre-FR', 'eng-GB'], 'demo.example.org', 'fr'),
            ], 'mattcch_group', '.demo.example.org', null, 'var/demo', 'demo_layout_config'),
            new Site('off_group', 'Off', SiteStatus::Disabled, 700, 'imal', [new SiteAccessDefinition('off', ['eng-GB'], 'off.example.org')], 'site_group'),
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
