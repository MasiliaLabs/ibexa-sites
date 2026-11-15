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

    public function testTheAdminSiteaccessesOfAServedSiteMayLogInToo(): void
    {
        $site = SiteBuilder::site(['status' => SiteStatus::Active, 'adminSiteaccesses' => [new SiteAccessDefinition('admin_demo', ['eng-GB'], 'admin.demo.example.org')]]);

        self::assertSame(['111', $this->crc('demo'), $this->crc('demo_fr'), $this->crc('admin_demo')], AnonymousLoginAccess::values(['111'], null, $site));
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

    public function testADraftOfTheRoleLeftBehindIsDiscardedBeforeANewOneIsMade(): void
    {
        $limitation = new \Ibexa\Contracts\Core\Repository\Values\User\Limitation\SiteAccessLimitation(['limitationValues' => ['111']]);
        $policy = new \Ibexa\Core\Repository\Values\User\Policy(['module' => 'user', 'function' => 'login', 'limitations' => [$limitation]]);
        $role = new \Ibexa\Core\Repository\Values\User\Role(['id' => 7, 'identifier' => 'Anonymous', 'policies' => [$policy]]);
        $draftPolicy = new \Ibexa\Core\Repository\Values\User\PolicyDraft(['innerPolicy' => $policy]);
        $draft = new \Ibexa\Core\Repository\Values\User\RoleDraft(['innerRole' => $role]);

        $calls = [];
        $roles = $this->createMock(\Ibexa\Contracts\Core\Repository\RoleService::class);
        $roles->method('loadRoleByIdentifier')->willReturn($role);
        $roles->method('loadRoleDraftByRoleId')->willReturnCallback(function () use (&$calls, $draft) {
            $calls[] = 'load draft';

            return $draft;
        });
        $roles->method('deleteRoleDraft')->willReturnCallback(function () use (&$calls): void {
            $calls[] = 'delete draft';
        });
        $roles->method('createRoleDraft')->willReturnCallback(function () use (&$calls, $draft) {
            $calls[] = 'create draft';

            return $draft;
        });
        $roles->method('newPolicyUpdateStruct')->willReturn(new \Ibexa\Core\Repository\Values\User\PolicyUpdateStruct());
        $roles->method('publishRoleDraft')->willReturnCallback(function () use (&$calls): void {
            $calls[] = 'publish';
        });
        $repository = $this->createMock(\Ibexa\Contracts\Core\Repository\Repository::class);
        $repository->method('getRoleService')->willReturn($roles);
        $repository->method('sudo')->willReturnCallback(static fn (callable $callback) => $callback($repository));

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(self::stringContains('was discarded'));

        (new AnonymousLoginAccess($repository, 'Anonymous', $logger))->sync(null, SiteBuilder::site(['status' => SiteStatus::Active]));

        self::assertSame(['load draft', 'delete draft', 'create draft', 'publish'], $calls);
    }
}
