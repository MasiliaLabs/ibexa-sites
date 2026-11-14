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
        $provider = new DynamicSiteAccessProvider($this->snapshot());

        self::assertSame(['blog', 'blog_ar', 'demo', 'demo_fr'], array_map(static fn (SiteAccess $s): string => $s->name, iterator_to_array($provider->getSiteAccesses(), false)));
        self::assertTrue($provider->isDefined('demo_fr'));
        self::assertFalse($provider->isDefined('off'));
        self::assertSame(['front_group', 'mattcch_group'], array_map(static fn ($g): string => $g->getName(), $provider->getSiteAccess('demo')->groups), 'what the registry does not set comes from the base group');
    }

    public function testTheProviderRefusesASiteaccessItDoesNotHave(): void
    {
        $this->expectException(NotFoundException::class);

        (new DynamicSiteAccessProvider($this->snapshot()))->getSiteAccess('fossil_exit');
    }

    public function testTheResolverGivesTheLanguageTheRootTheDesignAndTheTranslations(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());

        self::assertSame(['fre-FR'], $resolver->getParameter('languages', null, 'demo_fr'));
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
