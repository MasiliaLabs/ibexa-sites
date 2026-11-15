<?php

declare(strict_types=1);

namespace Masilia\IbexaSites;

use Doctrine\ORM\EntityManagerInterface;
use Masilia\Contracts\IbexaSites\Event\SiteChanged;
use Masilia\Contracts\IbexaSites\Problem;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteInvalid;
use Masilia\Contracts\IbexaSites\SiteNotFound;
use Masilia\Contracts\IbexaSites\SiteRegistry;
use Masilia\Contracts\IbexaSites\SiteSettings;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Masilia\IbexaSites\Persistence\Entity\SiteRecord;
use Masilia\IbexaSites\Persistence\SiteReader;
use Masilia\IbexaSites\Validation\SiteValidator;
use Psr\EventDispatcher\EventDispatcherInterface;

/** The registry in the database: every write is validated, kept in one transaction, and announced. */
readonly class DoctrineSiteRegistry implements SiteRegistry
{
    public function __construct(
        private EntityManagerInterface $entities,
        private SiteReader $reader,
        private SiteValidator $validator,
        private EventDispatcherInterface $events,
        private SiteSettings $settings,
    ) {
    }

    public function list(): array
    {
        return $this->reader->all();
    }

    public function find(string $identifier): ?Site
    {
        return $this->record($identifier)?->toSite();
    }

    public function check(Site $site, bool $isNew): array
    {
        return $this->validator->check($site, $isNew ? $this->list() : $this->others($site->identifier), $isNew);
    }

    public function problems(Site $site, bool $isNew): array
    {
        return array_map(static fn (Problem $p): string => $p->message, $this->check($site, $isNew));
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
        $this->keepsDefault($before, $site);

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
        $this->keepsDefault($before, $after);

        $record->apply($after);
        $this->entities->flush();
        $this->events->dispatch(new SiteChanged($before, $after));

        return $after;
    }

    public function delete(string $identifier): void
    {
        $record = $this->record($identifier) ?? throw new SiteNotFound($identifier);
        $before = $record->toSite();
        $this->keepsDefault($before, null);

        $this->entities->remove($record);
        $this->entities->flush();
        $this->events->dispatch(new SiteChanged($before, null));
    }

    /**
     * The siteaccess that answers when no host matches must stay served: a site that holds it cannot be disabled, deleted or lose it.
     *
     * @throws SiteInvalid
     */
    private function keepsDefault(Site $before, ?Site $after): void
    {
        $default = $this->settings->defaultSiteaccess();
        if ($default === null || !$before->status->isServed() || !in_array($default, $before->siteaccessNames(), true)) {
            return;
        }
        if ($after === null || !$after->status->isServed() || !in_array($default, $after->siteaccessNames(), true)) {
            throw new SiteInvalid([sprintf('The siteaccess "%s" of this site is the default one (it answers when no host matches): choose another default first.', $default)]);
        }
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
