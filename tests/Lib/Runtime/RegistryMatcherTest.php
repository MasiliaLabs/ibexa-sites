<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Masilia\IbexaSites\Runtime\RegistryMatcher;
use PHPUnit\Framework\TestCase;

final class RegistryMatcherTest extends TestCase
{
    use SitesFixture;

    protected function setUp(): void
    {
        $this->snapshot(true);
    }

    protected function tearDown(): void
    {
        RegistryMatcher::useSites(null);
    }

    private function matcher(string $host, string $path): RegistryMatcher
    {
        $matcher = new RegistryMatcher();
        $matcher->setRequest(new SimplifiedRequest('https', $host, 443, $path));

        return $matcher;
    }

    public function testAHostIsMatchedAndTheFirstLanguageIsAtTheRoot(): void
    {
        self::assertSame('demo', $this->matcher('demo.example.org', '/page')->match());
        self::assertSame('demo', $this->matcher('DEMO.example.org', '/')->match());
    }

    public function testAPrefixPicksTheLanguageAndIsStrippedFromTheRoute(): void
    {
        $matcher = $this->matcher('demo.example.org', '/fr/page');

        self::assertSame('demo_fr', $matcher->match());
        self::assertSame('/page', $matcher->analyseURI('/fr/page'));
        self::assertSame('/', $matcher->analyseURI('/fr'));
        self::assertSame('/fr/other?x=1', $matcher->analyseLink('/other?x=1'));
        self::assertSame('/fr', $matcher->analyseLink('/'));
    }

    public function testAPathThatOnlyLooksLikeAPrefixIsNotOne(): void
    {
        self::assertSame('demo', $this->matcher('demo.example.org', '/frontier')->match());
    }

    public function testEachHostOfASiteWithOneHostPerLanguageIsItsLanguage(): void
    {
        self::assertSame('blog', $this->matcher('blog.example.org', '/x')->match());
        self::assertSame('blog_ar', $this->matcher('blog-ar.example.org', '/x')->match());
        self::assertSame('/page', $this->matcher('blog-ar.example.org', '/')->analyseLink('/page'), 'no prefix with one host per language');
    }

    public function testTheHostOfAnAdminSiteaccessIsMatchedLikeAnyOther(): void
    {
        self::assertSame('admin_demo', $this->matcher('admin.demo.example.org', '/login')->match());
        self::assertSame('admin.demo.example.org', $this->matcher('demo.example.org', '/')->reverseMatch('admin_demo')?->getRequest()->getHost());
    }

    public function testAHostOfNoSiteOrOfADisabledOneIsNotMatched(): void
    {
        self::assertFalse($this->matcher('www.example.org', '/')->match());
        self::assertFalse($this->matcher('off.example.org', '/')->match());
    }

    public function testALinkToAnotherSiteaccessGetsItsHostAndItsPrefixWithoutTouchingTheRequestOfTheMatch(): void
    {
        $matcher = $this->matcher('demo.example.org', '/fr/page');
        $matcher->match();

        $english = $matcher->reverseMatch('demo');
        $arabic = $matcher->reverseMatch('blog_ar');
        $french = $matcher->reverseMatch('demo_fr');

        self::assertSame('demo.example.org', $english?->getRequest()->getHost());
        self::assertSame('/page', $english?->analyseLink('/page'));
        self::assertSame('blog-ar.example.org', $arabic?->getRequest()->getHost());
        self::assertSame('/fr/page', $french?->analyseLink('/page'));
        self::assertSame('/fr/x', $matcher->analyseLink('/x'), 'the request of the match keeps its own prefix');
        self::assertNull($matcher->reverseMatch('site'));
    }
}
