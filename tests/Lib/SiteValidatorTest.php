<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Validation\SiteValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SiteValidatorTest extends TestCase
{
    private function problems(array $with, array $others = [], bool $isNew = true): array
    {
        try {
            (new SiteValidator(new FakeFacts()))->validate(SiteBuilder::site($with), $others, $isNew);
        } catch (SiteInvalid $invalid) {
            return $invalid->problems;
        }

        return [];
    }

    public function testAValidSiteIsAccepted(): void
    {
        self::assertSame([], $this->problems([]));
        self::assertSame([], $this->problems(['adminSiteaccess' => 'admin', 'status' => SiteStatus::Active]));
    }

    private static function sa(string $name, array $languages = ['eng-GB'], string $host = 'demo.example.org', ?string $prefix = null): SiteAccessDefinition
    {
        return new SiteAccessDefinition($name, $languages, $host, $prefix);
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function refusals(): iterable
    {
        yield 'an identifier with capitals' => [['identifier' => 'Demo'], 'identifier "Demo" is not valid'];
        yield 'no name' => [['name' => ' '], 'name is required'];
        yield 'no siteaccess' => [['siteaccesses' => []], 'at least one siteaccess'];
        yield 'a siteaccess name that is not valid' => [['siteaccesses' => [self::sa('Demo Fr')]], 'siteaccess name "Demo Fr" is not valid'];
        yield 'a siteaccess given twice' => [['siteaccesses' => [self::sa('demo'), self::sa('demo', host: 'other.example.org')]], 'given twice'];
        yield 'two siteaccesses at the root of a host' => [['siteaccesses' => [self::sa('demo'), self::sa('demo_fr')]], 'Only one siteaccess can be at the root'];
        yield 'two siteaccesses on the same prefix' => [['siteaccesses' => [self::sa('demo', prefix: 'fr'), self::sa('demo_fr', prefix: 'fr')]], 'same address'];
        yield 'a prefix with a slash' => [['siteaccesses' => [self::sa('demo', prefix: 'a/b')]], 'prefix "a/b" is not valid'];
        yield 'a host that is not one' => [['siteaccesses' => [self::sa('demo', host: 'Demo Site')]], 'is not a host name'];
        yield 'a host in capitals' => [['siteaccesses' => [self::sa('demo', host: 'Demo.example.org')]], 'is not a host name'];
        yield 'no language' => [['siteaccesses' => [self::sa('demo', [])]], 'needs at least one language'];
        yield 'a language twice' => [['siteaccesses' => [self::sa('demo', ['eng-GB', 'eng-GB'])]], 'given twice for the siteaccess'];
        yield 'a language Ibexa does not have' => [['siteaccesses' => [self::sa('demo', ['eng-GB', 'spa-ES'])]], 'language "spa-ES" does not exist'];
        yield 'a root that does not exist' => [['rootLocationId' => 999], 'root location 999 does not exist'];
        yield 'a design that is not declared' => [['design' => 'nope'], 'design "nope" is not declared'];
        yield 'the admin design' => [['design' => 'admin'], 'design "admin" is not declared'];
        yield 'a base that is not a front group' => [['base' => 'admin_group'], 'not a group a site can take its settings from'];
        yield 'a directory that goes up' => [['varDir' => 'var/../etc'], 'directory "var/../etc" is not valid'];
        yield 'an absolute directory' => [['varDir' => '/var/demo'], 'is not valid'];
        yield 'a layout that does not exist' => [['layoutRemoteId' => 'nope'], 'No content has the remote id "nope"'];
        yield 'an admin that does not exist' => [['adminSiteaccess' => 'admin_x'], 'not an admin siteaccess'];
    }

    /** @param array<string, mixed> $with */
    #[DataProvider('refusals')]
    public function testWhatIsWrongIsSaid(array $with, string $expected): void
    {
        self::assertStringContainsString($expected, implode("\n", $this->problems($with)));
    }

    public function testADirectoryAndAnExistingLayoutAreAccepted(): void
    {
        self::assertSame([], $this->problems(['varDir' => 'var/demo-2', 'layoutRemoteId' => 'demo_layout_config']));
    }

    private static function admin(string $name = 'admin_demo', string $host = 'admin.demo.example.org'): SiteAccessDefinition
    {
        return new SiteAccessDefinition($name, ['eng-GB', 'fre-FR'], $host);
    }

    public function testAdminSiteaccessesFollowTheRulesOfAddressAndNamesOfTheOthers(): void
    {
        self::assertSame([], $this->problems(['adminSiteaccesses' => [self::admin()]]));
        self::assertStringContainsString('given twice', implode("\n", $this->problems(['adminSiteaccesses' => [self::admin('demo')]])));
        self::assertStringContainsString('Two siteaccesses answer on the same address', implode("\n", $this->problems(['adminSiteaccesses' => [self::admin('admin_demo', 'demo.example.org')]])));
        self::assertStringContainsString('is not a host name', implode("\n", $this->problems(['adminSiteaccesses' => [self::admin('admin_demo', 'Admin Demo')]])));
        self::assertStringContainsString('already used by another site', implode("\n", $this->problems(['adminSiteaccesses' => [self::admin('admin_other')]], [SiteBuilder::site(['identifier' => 'other', 'rootLocationId' => 501, 'siteaccesses' => [self::sa('x', host: 'x.example.org')], 'adminSiteaccesses' => [self::admin('admin_x')]])])));
    }

    public function testTheCookieDomainMustCoverTheHostsOfTheSiteAndNoOneElses(): void
    {
        $with = ['sessionCookieDomain' => '.demo.example.org', 'adminSiteaccesses' => [self::admin()]];

        self::assertSame([], $this->problems($with), 'the front and the admin are under the domain');
        self::assertStringContainsString('does not cover the host "admin.other.org"', implode("\n", $this->problems(['adminSiteaccesses' => [self::admin('admin_demo', 'admin.other.org')]] + ['sessionCookieDomain' => '.demo.example.org'])));
        self::assertStringContainsString('is not a cookie domain', implode("\n", $this->problems(['sessionCookieDomain' => '.org'])));
        self::assertStringContainsString('is not a cookie domain', implode("\n", $this->problems(['sessionCookieDomain' => '.Demo.example.org'])));
    }

    public function testABaseGroupThatHasASessionOfItsOwnNeedsACookieDomainOfTheSite(): void
    {
        self::assertStringContainsString('has a session of its own', implode("\n", $this->problems(['base' => 'session_group'])));
        self::assertSame([], $this->problems(['base' => 'session_group', 'sessionCookieDomain' => '.demo.example.org']));
        self::assertSame([], $this->problems(['base' => 'main_group']), 'a base group with no session of its own is fine');
    }

    public function testACookieDomainThatReachesAnotherSiteOrTheYamlIsRefused(): void
    {
        $wide = ['sessionCookieDomain' => '.example.org', 'siteaccesses' => [self::sa('demo', host: 'demo.example.org')]];
        $other = SiteBuilder::site(['identifier' => 'other', 'rootLocationId' => 501, 'siteaccesses' => [self::sa('x', host: 'x.example.org')]]);

        $problems = implode("\n", $this->problems($wide, [$other]));

        self::assertStringContainsString('would also be sent to "x.example.org", which belongs to another site', $problems);
        self::assertStringContainsString('would also be sent to "main.example.org"', $problems, 'a host of the YAML is another site too');
    }

    public function testEachProblemIsRelatedToTheFieldItIsAbout(): void
    {
        $site = SiteBuilder::site([
            'identifier' => 'Bad',
            'name' => '',
            'rootLocationId' => 999,
            'design' => 'nope',
            'varDir' => '../x',
            'layoutRemoteId' => 'nope',
            'siteaccesses' => [self::sa('demo', ['spa-ES'], 'Bad Host', 'a/b')],
            'adminSiteaccesses' => [self::admin('admin_demo', 'Worse Host')],
        ]);

        $paths = array_map(static fn (\Masilia\Contracts\IbexaSites\Problem $p): string => $p->path, (new SiteValidator(new FakeFacts()))->check($site, [], true));

        foreach (['identifier', 'name', 'rootLocationId', 'design', 'varDir', 'layoutRemoteId', 'siteaccesses[0].host', 'siteaccesses[0].uriPrefix', 'siteaccesses[0].languages', 'adminSiteaccesses[0].host'] as $expected) {
            self::assertContains($expected, $paths, $expected);
        }
    }

    public function testTheExceptionKeepsTheSentencesAndTheFields(): void
    {
        try {
            (new SiteValidator(new FakeFacts()))->validate(SiteBuilder::site(['name' => '']), [], true);
            self::fail('The site was accepted.');
        } catch (SiteInvalid $invalid) {
            self::assertSame(['The name is required, 100 characters at most.'], $invalid->problems);
            self::assertSame('name', $invalid->items[0]->path);
        }
    }

    public function testASiteWithoutABaseIsAccepted(): void
    {
        self::assertSame([], $this->problems(['base' => null]));
    }

    public function testAPrefixCanBeTheOnlySiteaccessOfAHostAndTheLanguagesAreFree(): void
    {
        self::assertSame([], $this->problems(['siteaccesses' => [self::sa('demo_fr', ['fre-FR', 'eng-GB'], 'demo.example.org', 'fr'), self::sa('demo_ar', ['ara-MA'], 'demo.example.org', 'ar')]]));
    }

    public function testAServedSiteMustNotClashWithWhatTheYamlStillDeclares(): void
    {
        $served = ['status' => SiteStatus::Active];

        self::assertStringContainsString('still declares', implode("\n", $this->problems(['identifier' => 'main_group'] + $served)));
        self::assertStringContainsString('still exists in the project', implode("\n", $this->problems(['siteaccesses' => [self::sa('main_fr')]] + $served)));
        self::assertStringContainsString('still answered by the project', implode("\n", $this->problems(['siteaccesses' => [self::sa('demo', host: 'main.example.org')]] + $served)));
        self::assertStringContainsString('still the root of a site', implode("\n", $this->problems(['rootLocationId' => 62] + $served)));
    }

    public function testADraftMayCarryWhatTheYamlStillDeclaresSoThatASiteCanBeTakenOverFromIt(): void
    {
        self::assertSame([], $this->problems(['identifier' => 'main_group', 'rootLocationId' => 62, 'siteaccesses' => [self::sa('main', host: 'main.example.org'), self::sa('main_fr', ['fre-FR'], 'main.example.org', 'fr')]]));
    }

    public function testASiteCannotTakeTheAddressTheNamesOrTheRootOfAnotherSiteOfTheRegistry(): void
    {
        $other = SiteBuilder::site(['identifier' => 'other']);

        $problems = implode("\n", $this->problems([], [$other]));

        self::assertStringContainsString('address demo.example.org is already used', $problems);
        self::assertStringContainsString('siteaccess name "demo" is already used', $problems);
        self::assertStringContainsString('location 500 is already the root of another site', $problems);
    }

    public function testASiteAlreadyThereIsRefusedAsANewOneButNotAsAChange(): void
    {
        $same = SiteBuilder::site(['siteaccesses' => [self::sa('x', host: 'x.example.org')], 'rootLocationId' => 501]);

        self::assertStringContainsString('already exists', implode("\n", $this->problems([], [$same], true)));
        self::assertSame([], $this->problems([], [], false));
    }

    public function testEveryProblemIsSaidAtOnce(): void
    {
        self::assertGreaterThanOrEqual(3, count($this->problems(['name' => '', 'siteaccesses' => [], 'design' => 'nope'])));
    }

    public function testTheHostsAndTheLanguagesOfASiteAreThoseOfItsSiteaccesses(): void
    {
        $site = SiteBuilder::site();

        self::assertSame(['demo', 'demo_fr'], $site->siteaccessNames());
        self::assertSame(['demo.example.org'], $site->hosts());
        self::assertSame(['eng-GB', 'fre-FR'], $site->languages());
        self::assertSame($site->toArray(), \Masilia\Contracts\IbexaSites\Site::fromArray($site->toArray())->toArray());
        $withAdmin = SiteBuilder::site(['adminSiteaccesses' => [self::admin()]]);
        self::assertSame(['demo', 'demo_fr', 'admin_demo'], $withAdmin->allSiteaccessNames());
        self::assertSame(['demo', 'demo_fr'], $withAdmin->siteaccessNames(), 'the fronts are what visitors see');
        self::assertTrue($withAdmin->isAdmin('admin_demo'));
        self::assertFalse($withAdmin->isAdmin('demo'));
        self::assertSame(['demo.example.org', 'admin.demo.example.org'], $withAdmin->hosts());
        self::assertSame($withAdmin->toArray(), \Masilia\Contracts\IbexaSites\Site::fromArray($withAdmin->toArray())->toArray());
    }
}
