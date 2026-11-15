<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteStatus;

/** A row of the registry (table masilia_site): the storage of a Site, its siteaccesses kept with it. */
#[ORM\Entity]
#[ORM\Table(name: 'masilia_site')]
class SiteRecord
{
    /** The identifier of a site is its key: it never changes. */
    #[ORM\Id]
    #[ORM\Column(length: 30)]
    private string $identifier;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 12)]
    private string $status;

    #[ORM\Column(name: 'root_location_id', type: Types::INTEGER)]
    private int $rootLocationId;

    #[ORM\Column(length: 80)]
    private string $design;

    #[ORM\Column(name: 'base_group', length: 80, nullable: true)]
    private ?string $base;

    #[ORM\Column(name: 'session_cookie_domain', length: 255, nullable: true)]
    private ?string $sessionCookieDomain;

    /** @var list<array<string, mixed>> */
    #[ORM\Column(type: Types::JSON)]
    private array $siteaccesses;

    /** @var list<array<string, mixed>> */
    #[ORM\Column(name: 'admin_siteaccesses', type: Types::JSON)]
    private array $adminSiteaccesses;

    #[ORM\Column(name: 'admin_siteaccess', length: 80, nullable: true)]
    private ?string $adminSiteaccess;

    #[ORM\Column(name: 'var_dir', length: 255, nullable: true)]
    private ?string $varDir;

    #[ORM\Column(name: 'layout_remote_id', length: 100, nullable: true)]
    private ?string $layoutRemoteId;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct(Site $site)
    {
        $this->identifier = $site->identifier;
        $this->apply($site);
    }

    public function apply(Site $site): void
    {
        $this->name = $site->name;
        $this->status = $site->status->value;
        $this->rootLocationId = $site->rootLocationId;
        $this->design = $site->design;
        $this->base = $site->base;
        $this->sessionCookieDomain = $site->sessionCookieDomain;
        $this->siteaccesses = array_map(static fn (SiteAccessDefinition $s): array => $s->toArray(), $site->siteaccesses);
        $this->adminSiteaccess = $site->adminSiteaccess;
        $this->adminSiteaccesses = array_map(static fn (SiteAccessDefinition $s): array => $s->toArray(), $site->adminSiteaccesses);
        $this->varDir = $site->varDir;
        $this->layoutRemoteId = $site->layoutRemoteId;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toSite(): Site
    {
        return new Site(
            $this->identifier,
            $this->name,
            SiteStatus::from($this->status),
            $this->rootLocationId,
            $this->design,
            array_map(static fn (array $row): SiteAccessDefinition => SiteAccessDefinition::fromArray($row), $this->siteaccesses),
            $this->base,
            $this->sessionCookieDomain,
            $this->adminSiteaccess,
            $this->varDir,
            $this->layoutRemoteId,
            array_map(static fn (array $row): SiteAccessDefinition => SiteAccessDefinition::fromArray($row), $this->adminSiteaccesses),
        );
    }
}
