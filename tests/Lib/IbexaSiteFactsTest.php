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
            $this->createMock(\Ibexa\Contracts\Core\Repository\ContentService::class),
            $this->createMock(LanguageService::class),
            $this->createMock(ConfigResolverInterface::class),
            [],
            ['front_group' => ['shop'], 'shop_group' => ['shop'], 'taken_over_group' => []],
            ['shop'],
            [],
            'admin_group',
            new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(['ibexa.site_access.config.shop_group.session' => ['name' => 'eZSESSID']]),
        );

        self::assertContains('shop_group', $facts->reservedNames());
        self::assertContains('shop', $facts->reservedNames());
        self::assertNotContains('taken_over_group', $facts->reservedNames());
        self::assertTrue($facts->groupHasSession('shop_group'));
        self::assertFalse($facts->groupHasSession('taken_over_group'));
    }
}
