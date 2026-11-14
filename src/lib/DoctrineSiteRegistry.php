<?php

declare(strict_types=1);

namespace Masilia\IbexaSites;

use Doctrine\ORM\EntityManagerInterface;
use Masilia\Contracts\IbexaSites\Event\SiteChanged;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Persistence\Entity\SiteRecord;
use Masilia\IbexaSites\Validation\SiteValidator;
use Psr\EventDispatcher\EventDispatcherInterface;

/** The registry in the database: every write is validated, kept in one transaction, and announced. */
readonly class DoctrineSiteRegistry implements SiteRegistry
{
    public function __construct(
        private EntityManagerInterface $entities,
        private SiteValidator $validator,
        private EventDispatcherInterface $events,
    ) {
    }

    public function list(): array
    {
        return array_map(
            static fn (SiteRecord $record): Site => $record->toSite(),
            $this->entities->getRepository(SiteRecord::class)->findBy([], ['identifier' => 'ASC']),
        );
    }

    public function find(string $identifier): ?Site
    {
        return $this->record($identifier)?->toSite();
    }

    public function create(Site $site): Site
    {
        $this->validator->validate($site, $this->list(), true);

        $this->entities->persist(new SiteRecord($site));
        $this->entities->flush();
        $this->events->dispatch(new SiteChanged(null, $site));

        return $site;
    }

    public function update(Site $site): Site
    {
        $record = $this->record($site->identifier) ?? throw new SiteNotFound($site->identifier);
        $before = $record->toSite();
        $this->validator->validate($site, $this->others($site->identifier), false);

        $record->apply($site);
        $this->entities->flush();
        $this->events->dispatch(new SiteChanged($before, $site));

        return $site;
    }

    public function setStatus(string $identifier, SiteStatus $status): Site
    {
        $record = $this->record($identifier) ?? throw new SiteNotFound($identifier);
        $before = $record->toSite();
        if ($before->status === $status) {
            return $before;
        }
        $after = $before->withStatus($status);
        // A site is only served once it is valid: the same rules as a write.
        $this->validator->validate($after, $this->others($identifier), false);

        $record->apply($after);
        $this->entities->flush();
        $this->events->dispatch(new SiteChanged($before, $after));

        return $after;
    }

    public function delete(string $identifier): void
    {
        $record = $this->record($identifier) ?? throw new SiteNotFound($identifier);
        $before = $record->toSite();

        $this->entities->remove($record);
        $this->entities->flush();
        $this->events->dispatch(new SiteChanged($before, null));
    }

    private function record(string $identifier): ?SiteRecord
    {
        return $this->entities->getRepository(SiteRecord::class)->findOneBy(['identifier' => $identifier]);
    }

    /** @return list<Site> */
    private function others(string $identifier): array
    {
        return array_values(array_filter($this->list(), static fn (Site $site): bool => $site->identifier !== $identifier));
    }
}
