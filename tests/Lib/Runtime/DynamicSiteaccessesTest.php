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

        self::assertSame(['blog', 'blog_ar', 'demo', 'demo_fr', 'admin_demo'], array_map(static fn (SiteAccess $s): string => $s->name, iterator_to_array($provider->getSiteAccesses(), false)));
        self::assertTrue($provider->isDefined('demo_fr'));
        self::assertFalse($provider->isDefined('off'));
        self::assertSame(['admin_group'], array_map(static fn ($g): string => $g->getName(), $provider->getSiteAccess('admin_demo')->groups), 'an admin siteaccess is in the admin group and nowhere else');
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

    public function testTheResolverGivesTheNameTheUrlTheContactAndTheAdminOfASite(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());

        self::assertSame('Demo', $resolver->getParameter('site_name', 'masilia_ibexa_sites', 'demo_fr'));
        self::assertSame('https://demo.example.org', $resolver->getParameter('site_url', 'masilia_ibexa_sites', 'demo_fr'), 'the host of the first siteaccess, without the prefix');
        self::assertSame('contact@demo.example.org', $resolver->getParameter('site_email', 'masilia_ibexa_sites', 'demo'));
        self::assertSame('1 rue Demo, Rabat', $resolver->getParameter('site_address', 'masilia_ibexa_sites', 'demo_group'));
        self::assertFalse($resolver->hasParameter('site_email', 'masilia_ibexa_sites', 'blog'), 'no email set: the project\'s default');
        self::assertSame('admin_demo', $resolver->getParameter('admin_siteaccess', 'masilia_editorial_toolbox', 'demo'));
        self::assertFalse($resolver->hasParameter('admin_siteaccess', 'masilia_editorial_toolbox', 'blog'), 'no admin: the project\'s default');
        self::assertFalse($resolver->hasParameter('site_url', 'masilia_ibexa_sites', 'admin_demo'), 'the siteaccess of the admin is not a front');
    }

    public function testAnAdminSiteaccessGetsItsLanguagesTheDirectoryAndTheSessionButNeitherTheDesignNorTheRootNorTheLayout(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());

        self::assertSame(['eng-GB', 'fre-FR'], $resolver->getParameter('languages', null, 'admin_demo'));
        self::assertSame('var/demo', $resolver->getParameter('var_dir', null, 'admin_demo'));
        self::assertSame('.demo.example.org', $resolver->getParameter('session', null, 'admin_demo')['cookie_domain']);
        foreach (['design', 'content.tree_root.location_id', 'translation_siteaccesses'] as $front) {
            self::assertFalse($resolver->hasParameter($front, null, 'admin_demo'), $front . ' is the admin group\'s business');
        }
        self::assertFalse($resolver->hasParameter('layout_remote_id', 'masilia_ibexa_blocks', 'admin_demo'));
    }

    public function testTheGroupOfASiteAnswersWhatItsSiteaccessesShare(): void
    {
        $resolver = new RegistryConfigResolver($this->snapshot());

        self::assertSame(500, $resolver->getParameter('content.tree_root.location_id', null, 'demo_group'));
        self::assertSame('mattcch', $resolver->getParameter('design', null, 'demo_group'));
        self::assertSame(['demo', 'demo_fr'], $resolver->getParameter('translation_siteaccesses', null, 'demo_group'));
        self::assertSame('var/demo', $resolver->getParameter('var_dir', null, 'demo_group'));
        self::assertSame('.demo.example.org', $resolver->getParameter('session', null, 'demo_group')['cookie_domain']);
        self::assertSame('demo_layout_config', $resolver->getParameter('layout_remote_id', 'masilia_ibexa_blocks', 'demo_group'));
        self::assertFalse($resolver->hasParameter('languages', null, 'demo_group'), 'the languages are those of a siteaccess');
        self::assertFalse($resolver->hasParameter('design', null, 'off_group'), 'a site that is not served has no scope');
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
