<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SiteSnapshotTest extends TestCase
{
    use SitesFixture;

    public function testOnlyTheServedSitesAreKnownAndTheirSiteaccessesAreFound(): void
    {
        $snapshot = $this->snapshot();

        self::assertSame(['blog', 'demo'], array_map(static fn ($site): string => $site->identifier, $snapshot->active()));
        self::assertSame('fre-FR', $snapshot->bySiteaccess('demo_fr')[1] ?? null);
        self::assertSame('ara-MA', $snapshot->bySiteaccess('blog_ar')[1] ?? null);
        self::assertNull($snapshot->bySiteaccess('off'), 'a disabled site has no siteaccess');
        self::assertNull($snapshot->bySiteaccess('site'));
    }

    public function testTheDatabaseIsReadOnceThenTheCacheAnswersUntilItIsInvalidated(): void
    {
        $reader = $this->createMock(SiteReader::class);
        $reader->expects(self::exactly(2))->method('all')->willReturn(self::sites());
        $cache = new ArrayAdapter();

        $first = new SiteSnapshot($reader, $cache);
        $first->active();
        $first->active();
        // Another request: the cache answers.
        self::assertCount(2, (new SiteSnapshot($reader, $cache))->active());
        // A write: the next read goes back to the database.
        $first->invalidate();
        self::assertCount(2, $first->active());
    }

    public function testNothingBreaksWhenTheDatabaseCannotBeRead(): void
    {
        $reader = $this->createMock(SiteReader::class);
        $reader->method('all')->willThrowException(new \RuntimeException('no table masilia_site'));

        $snapshot = new SiteSnapshot($reader, new ArrayAdapter());

        self::assertSame([], $snapshot->active());
        self::assertNull($snapshot->bySiteaccess('demo'));
    }
}
