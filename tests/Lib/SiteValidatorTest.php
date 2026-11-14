<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\Contracts\IbexaSites\LanguageMode;
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

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function refusals(): iterable
    {
        yield 'an identifier with capitals' => [['identifier' => 'Demo'], 'identifier "Demo" is not valid'];
        yield 'an identifier of the project' => [['identifier' => 'main'], 'siteaccess or a group of the project'];
        yield 'a siteaccess the language would take from the project' => [['identifier' => 'main', 'languages' => ['eng-GB', 'fre-FR']], 'siteaccess "main_fr" that this site would have already exists'];
        yield 'no name' => [['name' => ' '], 'name is required'];
        yield 'no host' => [['hosts' => []], 'at least one host'];
        yield 'two hosts with prefixes' => [['hosts' => ['a.example.org', 'b.example.org']], 'exactly one host'];
        yield 'a host that is not one' => [['hosts' => ['Demo Site']], 'is not a host name'];
        yield 'a host in capitals' => [['hosts' => ['Demo.example.org']], 'is not a host name'];
        yield 'a host of the project' => [['hosts' => ['main.example.org']], 'already used'];
        yield 'an admin host' => [['hosts' => ['admin.example.org']], 'already used'];
        yield 'no language' => [['languages' => []], 'at least one language'];
        yield 'a language twice' => [['languages' => ['eng-GB', 'eng-GB']], 'given twice'];
        yield 'a language Ibexa does not have' => [['languages' => ['eng-GB', 'spa-ES']], 'language "spa-ES" does not exist'];
        yield 'a root that does not exist' => [['rootLocationId' => 999], 'root location 999 does not exist'];
        yield 'the root of a site of the project' => [['rootLocationId' => 62], 'already the root of another site'];
        yield 'a design that is not declared' => [['design' => 'nope'], 'design "nope" is not declared'];
        yield 'the admin design' => [['design' => 'admin'], 'design "admin" is not declared'];
        yield 'a base that is not a front group' => [['base' => 'admin_group'], 'not a group a site can take its settings from'];
        yield 'an admin that does not exist' => [['adminSiteaccess' => 'admin_x'], 'not an admin siteaccess'];
    }

    /** @param array<string, mixed> $with */
    #[DataProvider('refusals')]
    public function testWhatIsWrongIsSaid(array $with, string $expected): void
    {
        self::assertStringContainsString($expected, implode("\n", $this->problems($with)));
    }

    public function testOneHostPerLanguageNeedsAsManyHostsAsLanguages(): void
    {
        self::assertSame([], $this->problems(['languageMode' => LanguageMode::Host, 'hosts' => ['en.example.org', 'fr.example.org']]));
        self::assertStringContainsString('give 2 hosts', implode("\n", $this->problems(['languageMode' => LanguageMode::Host, 'hosts' => ['en.example.org']])));
    }

    public function testASiteCannotTakeTheHostOrTheRootOfAnotherSiteOfTheRegistry(): void
    {
        $other = SiteBuilder::site(['identifier' => 'other', 'hosts' => ['demo.example.org'], 'rootLocationId' => 500]);

        $problems = implode("\n", $this->problems([], [$other]));

        self::assertStringContainsString('host "demo.example.org" is already used', $problems);
        self::assertStringContainsString('location 500 is already the root of another site', $problems);
    }

    public function testASiteAlreadyThereIsRefusedAsANewOneButNotAsAChange(): void
    {
        $same = SiteBuilder::site(['hosts' => ['other.example.org'], 'rootLocationId' => 501]);

        self::assertStringContainsString('already exists', implode("\n", $this->problems([], [$same], true)));
        self::assertSame([], $this->problems([], [], false));
    }

    public function testEveryProblemIsSaidAtOnce(): void
    {
        self::assertGreaterThanOrEqual(3, count($this->problems(['name' => '', 'hosts' => [], 'design' => 'nope'])));
    }

    public function testTheSiteaccessesOfALanguageCarryItsCode(): void
    {
        self::assertSame(['eng-GB' => 'demo', 'fre-FR' => 'demo_fr'], SiteBuilder::site()->siteaccesses());
    }
}
