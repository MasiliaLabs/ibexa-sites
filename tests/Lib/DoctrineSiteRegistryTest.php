<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests\Lib;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Masilia\Contracts\IbexaSites\Event\SiteChanged;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteSettings;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\DoctrineSiteRegistry;
use Masilia\IbexaSites\Persistence\Entity\SiteRecord;
use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Validation\SiteValidator;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/** The registry on a real (in memory) database: what is written is read back, validated, and announced. */
final class DoctrineSiteRegistryTest extends TestCase
{
    /** @var list<SiteChanged> */
    private array $events = [];
    private DoctrineSiteRegistry $registry;
    private string|null $default = null;
    private SiteSettings $settings;

    protected function setUp(): void
    {
        $entities = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/../../src/lib/Persistence/Entity'], true),
        );
        (new SchemaTool($entities))->createSchema([$entities->getClassMetadata(SiteRecord::class)]);
        $this->events = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
            $this->events[] = $event;

            return $event;
        });
        $this->default = null;
        $self = $this;
        $this->settings = new class($self) implements SiteSettings {
            public function __construct(private readonly DoctrineSiteRegistryTest $test)
            {
            }

            public function defaultSiteaccess(): ?string
            {
                return $this->test->defaultName();
            }

            public function defaultSiteaccessChoices(): array
            {
                return [];
            }

            public function setDefaultSiteaccess(?string $siteaccess): void
            {
            }
        };
        $this->registry = new DoctrineSiteRegistry($entities, new SiteReader($entities), new SiteValidator(new FakeFacts()), $dispatcher, $this->settings);
    }

    public function testASiteIsReadBackAsItWasWritten(): void
    {
        $site = SiteBuilder::site(['adminSiteaccess' => 'admin']);

        $this->registry->create($site);

        self::assertEquals($site, $this->registry->find('demo_group'));
        self::assertEquals([$site], $this->registry->list());
        self::assertNull($this->registry->find('ghost'));
    }

    public function testAnInvalidSiteIsNotKeptAndNothingIsAnnounced(): void
    {
        try {
            $this->registry->create(SiteBuilder::site(['design' => 'nope']));
            self::fail('A site with a design that does not exist was accepted.');
        } catch (SiteInvalid) {
        }

        self::assertSame([], $this->registry->list());
        self::assertSame([], $this->events);
    }

    public function testEveryWriteIsAnnouncedWithWhatItWasAndWhatItIs(): void
    {
        $this->registry->create(SiteBuilder::site());
        $this->registry->update(SiteBuilder::site(['name' => 'Demo 2']));
        $this->registry->setStatus('demo_group', SiteStatus::Active);
        $this->registry->delete('demo_group');

        self::assertCount(4, $this->events);
        self::assertNull($this->events[0]->before);
        self::assertSame('Demo', $this->events[1]->before?->name);
        self::assertSame('Demo 2', $this->events[1]->after?->name);
        self::assertSame(SiteStatus::Active, $this->events[2]->after?->status);
        self::assertNull($this->events[3]->after);
        self::assertSame('demo_group', $this->events[3]->identifier());
        self::assertSame([], $this->registry->list());
    }

    public function testTheSameStatusIsNotAChangeAndIsNotAnnounced(): void
    {
        $this->registry->create(SiteBuilder::site());

        $this->registry->setStatus('demo_group', SiteStatus::Draft);

        self::assertCount(1, $this->events, 'the same status is not a change');
    }

    public function testASecondSiteCannotTakeTheHostOrTheRootOfTheFirst(): void
    {
        $this->registry->create(SiteBuilder::site());

        $this->expectException(SiteInvalid::class);
        $this->registry->create(SiteBuilder::site(['identifier' => 'demo2_group', 'name' => 'Demo 2']));
    }

    public function testWhatIsNotThereIsSaidNotFound(): void
    {
        $this->expectException(SiteNotFound::class);

        $this->registry->setStatus('ghost', SiteStatus::Active);
    }

    public function testAChangeKeepsTheSiteItsOwnHostAndRoot(): void
    {
        $this->registry->create(SiteBuilder::site());

        $changed = $this->registry->update(SiteBuilder::site(['name' => 'Renamed']));

        self::assertSame('Renamed', $this->registry->find('demo_group')?->name);
        self::assertSame('Renamed', $changed->name);
    }

    public function defaultName(): ?string
    {
        return $this->default;
    }

    public function testTheSiteThatHoldsTheDefaultSiteaccessCannotBeDisabledNorDeletedNorLoseIt(): void
    {
        $this->registry->create(SiteBuilder::site(['status' => SiteStatus::Active]));
        $this->default = 'demo_fr';

        foreach ([
            fn () => $this->registry->setStatus('demo_group', SiteStatus::Disabled),
            fn () => $this->registry->delete('demo_group'),
            fn () => $this->registry->update(SiteBuilder::site(['status' => SiteStatus::Active, 'siteaccesses' => [new \Masilia\Contracts\IbexaSites\SiteAccessDefinition('demo', ['eng-GB'], 'demo.example.org')]])),
        ] as $change) {
            try {
                $change();
                self::fail('The default siteaccess was let go.');
            } catch (SiteInvalid $invalid) {
                self::assertStringContainsString('is the default one', implode(' ', $invalid->problems));
            }
        }
        self::assertSame(SiteStatus::Active, $this->registry->find('demo_group')?->status);

        $this->default = null;
        $this->registry->setStatus('demo_group', SiteStatus::Disabled);
        self::assertSame(SiteStatus::Disabled, $this->registry->find('demo_group')?->status);
    }

    public function testTheProblemsOfASiteAreToldWithoutWritingAnything(): void
    {
        self::assertSame([], $this->registry->problems(SiteBuilder::site(), true));
        self::assertNotSame([], $this->registry->problems(SiteBuilder::site(['design' => 'nope']), true));
        self::assertSame([], $this->registry->list(), 'nothing was written');

        $this->registry->create(SiteBuilder::site());
        self::assertNotSame([], $this->registry->problems(SiteBuilder::site(), true), 'as a new one it is already there');
        self::assertSame([], $this->registry->problems(SiteBuilder::site(['name' => 'Renamed']), false), 'as a change it is acceptable');
    }
}
