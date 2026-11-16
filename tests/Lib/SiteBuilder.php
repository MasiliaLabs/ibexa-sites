<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteStatus;

/** A valid site of FakeFacts, to change one thing at a time. */
final class SiteBuilder
{
    /** @param array<string, mixed> $with */
    public static function site(array $with = []): Site
    {
        $values = $with + [
            'identifier' => 'demo_group',
            'name' => 'Demo',
            'status' => SiteStatus::Draft,
            'rootLocationId' => 500,
            'design' => 'brand',
            'siteaccesses' => [
                new SiteAccessDefinition('demo', ['eng-GB'], 'demo.example.org'),
                new SiteAccessDefinition('demo_fr', ['fre-FR', 'eng-GB'], 'demo.example.org', 'fr'),
            ],
            'base' => 'main_group',
            'sessionCookieDomain' => null,
            'adminSiteaccess' => null,
        ];

        return new Site(...$values);
    }
}
