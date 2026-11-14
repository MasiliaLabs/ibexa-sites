<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites\Event;

use Masilia\Contracts\IbexaSites\Site;

/** A site was created, changed or deleted: what depends on the registry (caches, the roles that let visitors in) follows. */
final readonly class SiteChanged
{
    /** @param Site|null $before null for a creation; @param Site|null $after null for a deletion */
    public function __construct(public ?Site $before, public ?Site $after)
    {
    }

    public function identifier(): string
    {
        $site = $this->after ?? $this->before;

        return $site === null ? '' : $site->identifier;
    }
}
