<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\IbexaSites\Persistence\Entity\SiteRecord;

/** Reads the sites from the database, and nothing else: what the run time needs depends on it, not on the whole registry. */
readonly class SiteReader
{
    public function __construct(private EntityManagerInterface $entities)
    {
    }

    /** @return list<Site> */
    public function all(): array
    {
        return array_map(
            static fn (SiteRecord $record): Site => $record->toSite(),
            $this->entities->getRepository(SiteRecord::class)->findBy([], ['identifier' => 'ASC']),
        );
    }
}
