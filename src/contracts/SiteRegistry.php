<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/**
 * The sites created at run time. The one way to write them, for the admin and for the assistant: every write is validated against
 * what Ibexa has (languages, designs, locations, the hosts already taken) and announced with a SiteChanged event.
 */
interface SiteRegistry
{
    /** @return list<Site> */
    public function list(): array;

    public function find(string $identifier): ?Site;

    /**
     * What is wrong with a site, without writing anything: the problems a {@see create()} (or an {@see update()} when $isNew is
     * false) would refuse it with, empty when it is acceptable. For checking before a person approves a change.
     *
     * @return list<string>
     */
    public function problems(Site $site, bool $isNew): array;

    /** @throws SiteInvalid */
    public function create(Site $site): Site;

    /**
     * Replaces the site of that identifier. The identifier is its name and does not change.
     *
     * @throws SiteInvalid
     * @throws SiteNotFound
     */
    public function update(Site $site): Site;

    /**
     * @throws SiteInvalid When the site cannot be served as it is
     * @throws SiteNotFound
     */
    public function setStatus(string $identifier, SiteStatus $status): Site;

    /** @throws SiteNotFound */
    public function delete(string $identifier): void;
}
