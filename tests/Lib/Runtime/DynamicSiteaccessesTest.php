<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Masilia\IbexaSites\Runtime\DynamicSiteAccessProvider;
use Masilia\IbexaSites\Runtime\RegistryConfigResolver;
use PHPUnit\Framework\TestCase;

final class DynamicSiteaccessesTest extends TestCase
{
    use SitesFixture;

    public function testTheProviderKnowsTheSiteaccessesOfTheServedSitesWithTheirGroups(): void
    {
        $provider = new DynamicSiteAccessProvider($this->snapshot(), 'admin_group');

        self::assertSame(['blog', 'blog_ar', 'demo', 'demo_fr'], array_map(static fn (SiteAccess $s): string => $s->name, iterator_to_array($provider->getSiteAccesses(), false)));
        self::assertTrue($provider->isDefined('demo_fr'));
        self::assertFalse($provider->isDefined('off'));
        self::assertSame(['front_group', 'demo_group', 'mattcch_group'], array_map(static fn ($g): string => $g->getName(), $provider->getSiteAccess('demo')->groups), 'the group of the site, then the base group the rest comes from');
    }

    public function testTheProviderRefusesASiteaccessItDoesNotHave(): void
    {
        $this->expectException(NotFoundException::class);

        (new DynamicSiteAccessProvider($this->snapshot(), 'admin_group'))->getSiteAccess('fossil_exit');
    }

    public function testTheResolverGivesTheLanguageTheRootTheDesignAndTheTranslations(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());

        self::assertSame(['fre-FR', 'eng-GB'], $resolver->getParameter('languages', null, 'demo_fr'), 'the languages of the siteaccess, with its fallback');
        self::assertSame(['name' => 'IBX_SESSION_ID_' . md5('demo_group'), 'cookie_domain' => '.demo.example.org'], $resolver->getParameter('session', null, 'demo'));
        self::assertSame('var/demo', $resolver->getParameter('var_dir', null, 'demo_fr'));
        self::assertSame('demo_layout_config', $resolver->getParameter('layout_remote_id', 'masilia_ibexa_blocks', 'demo'));
        self::assertFalse($resolver->hasParameter('layout_remote_id', null, 'demo'), 'the layout is answered in the namespace of masilia/ibexa-blocks only');
        self::assertFalse($resolver->hasParameter('session', null, 'blog'), 'no cookie domain set: the groups answer');
        self::assertFalse($resolver->hasParameter('var_dir', null, 'blog'), 'no directory set: the project\'s own');
        self::assertSame(500, $resolver->getParameter('content.tree_root.location_id', null, 'demo'));
        self::assertSame('mattcch', $resolver->getParameter('design', null, 'demo_fr'));
        self::assertSame(['demo', 'demo_fr'], $resolver->getParameter('translation_siteaccesses', null, 'demo'));
        self::assertSame(['ara-MA'], $resolver->getParameter('languages', null, 'blog_ar'));
    }

    public function testTheResolverUsesTheCurrentSiteaccessWithoutAScope(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());
        $resolver->setSiteAccess(new SiteAccess('blog'));

        self::assertSame(600, $resolver->getParameter('content.tree_root.location_id'));
    }

    public function testTheResolverLeavesEverythingElseToTheOthers(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());

        self::assertFalse($resolver->hasParameter('image_variations', null, 'demo'), 'not set by the registry: the groups answer');
        self::assertFalse($resolver->hasParameter('languages', null, 'site_fr'), 'a siteaccess of the project is not its business');
        self::assertFalse($resolver->hasParameter('languages', null, 'off'));
        $this->expectException(ParameterNotFoundException::class);
        $resolver->getParameter('design', null, 'fossil_exit');
    }
}
