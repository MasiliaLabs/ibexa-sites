<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Access\AnonymousLoginAccess;
use PHPUnit\Framework\TestCase;

/** Visitors enter the siteaccesses of the served sites only: what the user/login list becomes at each change. */
final class AnonymousLoginAccessTest extends TestCase
{
    private function crc(string $name): string
    {
        return sprintf('%u', crc32($name));
    }

    public function testAServedSiteIsAddedAndWhatWasThereStays(): void
    {
        $values = AnonymousLoginAccess::values(['111', '222'], null, SiteBuilder::site(['status' => SiteStatus::Active]));

        self::assertSame(['111', '222', $this->crc('demo'), $this->crc('demo_fr')], $values);
    }

    public function testADraftIsNotAdded(): void
    {
        self::assertSame(['111'], AnonymousLoginAccess::values(['111'], null, SiteBuilder::site()));
    }

    public function testADisabledOrDeletedSiteIsTakenOut(): void
    {
        $active = SiteBuilder::site(['status' => SiteStatus::Active]);
        $existing = ['111', $this->crc('demo'), $this->crc('demo_fr')];

        self::assertSame(['111'], AnonymousLoginAccess::values($existing, $active, $active->withStatus(SiteStatus::Disabled)));
        self::assertSame(['111'], AnonymousLoginAccess::values($existing, $active, null));
    }

    public function testAChangeOfSiteaccessesMovesThem(): void
    {
        $before = SiteBuilder::site(['status' => SiteStatus::Active]);
        $after = SiteBuilder::site(['status' => SiteStatus::Active, 'siteaccesses' => [new SiteAccessDefinition('demo', ['eng-GB'], 'demo.example.org'), new SiteAccessDefinition('demo_ar', ['ara-MA'], 'demo.example.org', 'ar')]]);

        $values = AnonymousLoginAccess::values(['111', $this->crc('demo'), $this->crc('demo_fr')], $before, $after);

        self::assertSame(['111', $this->crc('demo'), $this->crc('demo_ar')], $values);
    }

    public function testNothingChangesForASiteThatStaysADraft(): void
    {
        self::assertSame(['111'], AnonymousLoginAccess::values(['111'], SiteBuilder::site(), SiteBuilder::site(['name' => 'Renamed'])));
    }
}
