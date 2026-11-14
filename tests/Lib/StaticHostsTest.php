<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\IbexaSites\Ibexa\StaticHosts;
use PHPUnit\Framework\TestCase;

final class StaticHostsTest extends TestCase
{
    public function testTheHostsOfTheMatchersAreFoundAtAnyDepth(): void
    {
        $match = [
            'Compound\\LogicalAnd' => [
                'site_fr' => ['matchers' => ['Map\\URI' => ['fr' => true], 'Map\\Host' => ['Main.Example.org' => true]], 'match' => 'site_fr'],
            ],
            'Map\\Host' => ['admin.example.org' => 'admin', 'main.example.org' => 'main'],
            'Map\\URI' => ['x' => 'y'],
        ];

        self::assertSame(['main.example.org', 'admin.example.org'], StaticHosts::from($match));
    }
}
