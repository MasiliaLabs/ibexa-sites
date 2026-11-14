<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib\Runtime;

use Masilia\IbexaSites\Runtime\SiteaccessesByLanguage;
use PHPUnit\Framework\TestCase;

final class SiteaccessesByLanguageTest extends TestCase
{
    use SitesFixture;

    public function testTheSiteaccessesOfTheSitesAreAddedByTheirMainLanguage(): void
    {
        $table = SiteaccessesByLanguage::merge(['eng-GB' => ['site'], 'fre-FR' => ['site_fr']], $this->snapshot());

        self::assertSame(['site', 'blog', 'demo'], $table['eng-GB']);
        self::assertSame(['site_fr', 'demo_fr'], $table['fre-FR']);
        self::assertSame(['blog_ar'], $table['ara-MA']);
        self::assertSame($table, SiteaccessesByLanguage::merge($table, $this->snapshot()), 'merging twice adds nothing');
    }
}
