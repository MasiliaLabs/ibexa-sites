<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Masilia\IbexaSites\Ibexa\StaticAddresses;
use Masilia\IbexaSites\Ibexa\StaticSites;
use PHPUnit\Framework\TestCase;

final class StaticSitesTest extends TestCase
{
    private const MATCH = [
        'Compound\LogicalAnd' => [
            'shop_fr' => ['matchers' => ['Map\URI' => ['fr' => true], 'Map\Host' => ['shop.example.org' => true]], 'match' => 'shop_fr'],
            'ignored' => ['matchers' => ['Map\URI' => ['x' => true]], 'match' => 'ignored'],
        ],
        'Map\Host' => ['shop.example.org' => 'shop', 'Blog.example.org' => 'blog'],
        '\Some\Matcher' => ['value' => null],
    ];

    public function testTheAddressOfEachSiteaccessIsReadFromTheMatching(): void
    {
        self::assertSame([
            'shop_fr' => ['host' => 'shop.example.org', 'prefix' => 'fr'],
            'shop' => ['host' => 'shop.example.org', 'prefix' => null],
            'blog' => ['host' => 'blog.example.org', 'prefix' => null],
        ], StaticAddresses::from(self::MATCH));
    }

    public function testEachFrontGroupBecomesADraftSiteWithWhatTheConfigurationResolves(): void
    {
        $values = ['languages' => ['shop' => ['eng-GB'], 'shop_fr' => ['fre-FR', 'eng-GB'], 'blog' => ['eng-GB']], 'design' => ['shop' => 'imal'], 'content.tree_root.location_id' => ['shop' => 62], 'session.cookie_domain' => ['shop' => '.shop.example.org'], 'var_dir' => ['shop' => 'var/shop', 'default' => 'var/site'], 'layout_remote_id' => ['shop' => 'shop_layout'], 'site_name' => ['shop_group' => 'The Shop'], 'site_email' => ['shop_group' => 'hi@shop.example.org'], 'site_address' => ['shop_group' => '1 Shop Street']];
        $config = $this->createMock(ConfigResolverInterface::class);
        $config->method('hasParameter')->willReturnCallback(static fn (string $n, $ns, string $scope): bool => isset($values[$n][$scope]));
        $config->method('getParameter')->willReturnCallback(static fn (string $n, $ns, string $scope): mixed => $values[$n][$scope]);
        $groups = ['front_group' => ['shop', 'shop_fr', 'blog'], 'shop_group' => ['shop', 'shop_fr', 'shop'], 'admin_group' => ['admin'], 'lost_group' => ['nowhere']];

        $skipped = [];
        $sites = (new StaticSites($config, $groups, self::MATCH, 'admin_group'))->all($skipped);

        self::assertCount(1, $sites);
        self::assertSame('shop_group', $sites[0]->identifier);
        self::assertSame(62, $sites[0]->rootLocationId);
        self::assertNull($sites[0]->sessionCookieDomain, 'the session of a site taken over stays the one of the YAML');
        self::assertSame('var/shop', $sites[0]->varDir, 'a directory that is not the project\'s default is kept');
        self::assertSame('shop_layout', $sites[0]->layoutRemoteId);
        self::assertSame(['The Shop', 'hi@shop.example.org', '1 Shop Street'], [$sites[0]->name, $sites[0]->email, $sites[0]->address], 'the name, the contact and the address the project sets for the group');
        self::assertSame(['shop', 'shop_fr'], $sites[0]->siteaccessNames(), 'a member listed twice counts once');
        self::assertSame(['fre-FR', 'eng-GB'], $sites[0]->siteaccess('shop_fr')?->languages);
        self::assertSame(['lost_group: the siteaccess "nowhere" has no host in siteaccess.match.'], $skipped);
    }
}
