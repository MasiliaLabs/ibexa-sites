<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Masilia\IbexaSites\Runtime\SiteGroups;
use PHPUnit\Framework\TestCase;

final class SiteGroupsTest extends TestCase
{
    use SitesFixture;

    public function testTheGroupsAreCompletedLikeTheYamlWouldDeclareThem(): void
    {
        $groups = SiteGroups::groups(['front_group' => ['site'], 'admin_group' => ['admin'], 'mattcch_group' => []], $this->snapshot(), 'admin_group');

        self::assertSame(['site', 'blog', 'blog_ar', 'demo', 'demo_fr'], $groups['front_group']);
        self::assertSame(['admin', 'admin_demo'], $groups['admin_group']);
        self::assertSame(['demo', 'demo_fr'], $groups['demo_group']);
        self::assertSame(['demo', 'demo_fr'], $groups['mattcch_group'], 'a group declared empty gets the siteaccesses of the site that takes it over (as its base)');
        self::assertSame($groups, SiteGroups::groups($groups, $this->snapshot(), 'admin_group'), 'completing twice adds nothing');
    }

    public function testEachSiteaccessKnowsItsGroups(): void
    {
        $bySiteaccess = SiteGroups::bySiteaccess(['site' => ['front_group']], $this->snapshot(), 'admin_group');

        self::assertSame(['front_group'], $bySiteaccess['site']);
        self::assertSame(['front_group', 'demo_group', 'mattcch_group'], $bySiteaccess['demo_fr']);
        self::assertSame(['admin_group'], $bySiteaccess['admin_demo']);
        self::assertArrayNotHasKey('off', $bySiteaccess, 'a disabled site has no siteaccess');
    }
}
