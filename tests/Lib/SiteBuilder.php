<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteStatus;

/** A valid site of FakeFacts, to change one thing at a time. */
final class SiteBuilder
{
    /** @param array<string, mixed> $with */
    public static function site(array $with = []): Site
    {
        $values = $with + [
            'identifier' => 'demo',
            'name' => 'Demo',
            'status' => SiteStatus::Draft,
            'hosts' => ['demo.example.org'],
            'languages' => ['eng-GB', 'fre-FR'],
            'languageMode' => LanguageMode::Prefix,
            'rootLocationId' => 500,
            'design' => 'imal',
            'base' => 'main_group',
            'adminSiteaccess' => null,
        ];

        return new Site(...$values);
    }
}
