<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\DoctrineSiteSettings;
use Masilia\IbexaSites\Persistence\Entity\SettingRecord;
use Masilia\IbexaSites\Persistence\Entity\SiteRecord;
use Masilia\IbexaSites\Persistence\SettingReader;
use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Runtime\DefaultSiteaccess;
use Masilia\IbexaSites\Runtime\SiteSnapshot;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/** The siteaccess that answers when no host matches: what can be chosen, and what the run time makes of the choice. */
final class DoctrineSiteSettingsTest extends TestCase
{
    private EntityManager $entities;
    private DoctrineSiteSettings $settings;
    private DefaultSiteaccess $default;

    protected function setUp(): void
    {
        $this->entities = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../../src/lib/Persistence/Entity'], true),
        );
        (new SchemaTool($this->entities))->createSchema([$this->entities->getClassMetadata(SiteRecord::class), $this->entities->getClassMetadata(SettingRecord::class)]);
        $reader = new SiteReader($this->entities);
        $cache = new ArrayAdapter();
        $this->default = new DefaultSiteaccess(new SettingReader($this->entities), new SiteSnapshot($reader, $cache), $cache, ['main', 'main_fr', 'admin']);
        $this->settings = new DoctrineSiteSettings($this->entities, new SettingReader($this->entities), $reader, new FakeFacts(), $this->default);
    }

    private function addSite(SiteStatus $status): void
    {
        $this->entities->persist(new SiteRecord(SiteBuilder::site(['status' => $status])));
        $this->entities->flush();
    }

    public function testTheChoiceIsKeptAndCanBeGivenUp(): void
    {
        self::assertNull($this->settings->defaultSiteaccess());

        $this->settings->setDefaultSiteaccess('main_fr');
        self::assertSame('main_fr', $this->settings->defaultSiteaccess());

        $this->settings->setDefaultSiteaccess('main');
        self::assertSame('main', $this->settings->defaultSiteaccess(), 'a second choice replaces the first');

        $this->settings->setDefaultSiteaccess(null);
        self::assertNull($this->settings->defaultSiteaccess());
    }

    public function testOnlyAFrontOfTheProjectOrOfAServedSiteCanBeChosen(): void
    {
        $this->addSite(SiteStatus::Disabled);

        foreach (['admin', 'ghost', 'demo'] as $refused) {
            try {
                $this->settings->setDefaultSiteaccess($refused);
                self::fail($refused . ' was accepted.');
            } catch (SiteInvalid $invalid) {
                self::assertStringContainsString('can answer by default', implode(' ', $invalid->problems));
            }
        }

        $this->entities->getRepository(SiteRecord::class)->find('demo_group')?->apply(SiteBuilder::site(['status' => SiteStatus::Active]));
        $this->entities->flush();
        $this->settings->setDefaultSiteaccess('demo_fr');
        self::assertSame('demo_fr', $this->settings->defaultSiteaccess());
    }

    public function testTheRunTimeUsesTheChoiceWhileItExistsAndTheYamlOtherwise(): void
    {
        self::assertSame('main', $this->default->name('main'));

        $this->settings->setDefaultSiteaccess('main_fr');
        self::assertSame('main_fr', $this->default->name('main'), 'the choice is known at once');

        $this->entities->persist(new SettingRecord('ignored', 'x'));
        $this->entities->flush();
        $conn = $this->entities->getConnection();
        $conn->executeStatement("update masilia_setting set value = 'ghost' where name = 'default_siteaccess'");
        $this->entities->clear();
        $this->default->invalidate();
        self::assertSame('main', $this->default->name('main'), 'a siteaccess that is gone is not served: the YAML answers');
    }
}
