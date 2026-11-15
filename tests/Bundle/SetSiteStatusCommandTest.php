<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Bundle;

use Masilia\Bundle\IbexaSites\Command\SetSiteStatusCommand;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Tests\Lib\SiteBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class SetSiteStatusCommandTest extends TestCase
{
    /** @var array<string, Site> */
    private array $sites = [];

    private function command(): CommandTester
    {
        $this->sites = [
            'a_group' => SiteBuilder::site(['identifier' => 'a_group']),
            'b_group' => SiteBuilder::site(['identifier' => 'b_group', 'status' => SiteStatus::Active]),
            'bad_group' => SiteBuilder::site(['identifier' => 'bad_group']),
        ];
        $test = $this;
        $registry = $this->createMock(SiteRegistry::class);
        $registry->method('list')->willReturnCallback(fn (): array => array_values($test->sites));
        $registry->method('setStatus')->willReturnCallback(function (string $identifier, SiteStatus $status) use ($test): Site {
            $site = $test->sites[$identifier] ?? throw new SiteNotFound($identifier);
            if ($identifier === 'bad_group') {
                throw new SiteInvalid(['The host "x" is still answered by the project\'s YAML: take it out first.']);
            }

            return $test->sites[$identifier] = $site->withStatus($status);
        });

        return new CommandTester(new SetSiteStatusCommand($registry));
    }

    public function testNamedSitesAreActivated(): void
    {
        $tester = $this->command();

        $code = $tester->execute(['status' => 'active', 'identifiers' => ['a_group']]);

        self::assertSame(0, $code);
        self::assertStringContainsString('a_group is active', $tester->getDisplay());
        self::assertSame(SiteStatus::Active, $this->sites['a_group']->status);
    }

    public function testAllTheSitesNotYetInThatStatusAreChangedAndAFailureIsSaidAndShownInTheExitCode(): void
    {
        $tester = $this->command();

        $code = $tester->execute(['status' => 'active', '--all' => true]);

        self::assertSame(1, $code, 'one site could not be activated');
        self::assertStringContainsString('a_group is active', $tester->getDisplay());
        self::assertStringNotContainsString('b_group', $tester->getDisplay(), 'it was active already');
        self::assertStringContainsString('bad_group left as it is', $tester->getDisplay());
        self::assertStringContainsString('still answered by the project', $tester->getDisplay());
    }

    public function testAnUnknownSiteIsReportedAndANonsenseStatusIsRefused(): void
    {
        $tester = $this->command();

        self::assertSame(1, $tester->execute(['status' => 'disabled', 'identifiers' => ['ghost_group']]));
        self::assertStringContainsString('ghost_group is not in the registry', $tester->getDisplay());
        self::assertSame(2, $tester->execute(['status' => 'draft', 'identifiers' => ['a_group']]));
        self::assertSame(0, $tester->execute(['status' => 'active']), 'nothing named: nothing to do');
    }
}
