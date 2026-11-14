<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Masilia\IbexaSites\Ibexa\IbexaSiteFacts;
use PHPUnit\Framework\TestCase;

final class IbexaSiteFactsTest extends TestCase
{
    public function testAGroupDeclaredEmptyIsNotReservedButTheOthersAre(): void
    {
        $facts = new IbexaSiteFacts(
            $this->createMock(LocationService::class),
            $this->createMock(LanguageService::class),
            $this->createMock(ConfigResolverInterface::class),
            [],
            ['front_group' => ['shop'], 'shop_group' => ['shop'], 'taken_over_group' => []],
            ['shop'],
            [],
            'admin_group',
        );

        self::assertContains('shop_group', $facts->reservedNames());
        self::assertContains('shop', $facts->reservedNames());
        self::assertNotContains('taken_over_group', $facts->reservedNames());
    }
}
