<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Input\SiteInput;
use PHPUnit\Framework\TestCase;

final class SiteInputTest extends TestCase
{
    public function testWhatAScriptWritesIsCleanedAndTheIdentifierAndStatusAreNotItsToGive(): void
    {
        $site = SiteInput::site([
            'identifier' => 'hacked_group',
            'status' => 'active',
            'name' => '  Demo  ',
            'rootLocationId' => '500',
            'design' => ' imal ',
            'base' => '',
            'sessionCookieDomain' => '   ',
            'varDir' => ' var/demo ',
            'layoutRemoteId' => null,
            'siteaccesses' => [
                ['name' => ' demo ', 'languages' => [' eng-GB ', ''], 'host' => ' Demo.Example.ORG ', 'uriPrefix' => ''],
                ['name' => 'demo_fr', 'languages' => ['fre-FR', 'eng-GB'], 'host' => 'demo.example.org', 'uriPrefix' => ' /FR/ '],
                'not a row',
            ],
            'adminSiteaccesses' => [['name' => 'admin_demo', 'languages' => ['eng-GB'], 'host' => 'admin.demo.example.org']],
        ], ' demo_group ', SiteStatus::Draft);

        self::assertSame('demo_group', $site->identifier, 'the identifier comes from the address');
        self::assertSame(SiteStatus::Draft, $site->status, 'the status comes from the server');
        self::assertSame('Demo', $site->name);
        self::assertSame(500, $site->rootLocationId);
        self::assertNull($site->base, 'an empty value is no value');
        self::assertNull($site->sessionCookieDomain);
        self::assertNull($site->layoutRemoteId);
        self::assertSame('var/demo', $site->varDir);
        self::assertSame(['demo', 'demo_fr'], $site->siteaccessNames());
        self::assertSame(['eng-GB'], $site->siteaccess('demo')?->languages);
        self::assertSame('demo.example.org', $site->siteaccess('demo')?->host);
        self::assertNull($site->siteaccess('demo')?->uriPrefix);
        self::assertSame('fr', $site->siteaccess('demo_fr')?->uriPrefix);
        self::assertTrue($site->isAdmin('admin_demo'));
    }

    public function testNonsenseBecomesEmptyValuesTheRegistryRefusesFieldByField(): void
    {
        $site = SiteInput::site(['rootLocationId' => 'abc', 'siteaccesses' => 'x', 'name' => ['a']], 'x_group', SiteStatus::Draft);

        self::assertSame(0, $site->rootLocationId);
        self::assertSame([], $site->siteaccesses);
        self::assertSame('', $site->name);
    }
}
