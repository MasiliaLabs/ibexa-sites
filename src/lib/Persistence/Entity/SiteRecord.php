<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Persistence\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Masilia\Contracts\IbexaSites\LanguageMode;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteStatus;

/** A row of the registry (table masilia_site): the storage of a Site. */
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

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $hosts;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $languages;

    #[ORM\Column(name: 'language_mode', length: 8)]
    private string $languageMode;

    #[ORM\Column(name: 'root_location_id', type: Types::INTEGER)]
    private int $rootLocationId;

    #[ORM\Column(length: 80)]
    private string $design;

    #[ORM\Column(name: 'base_group', length: 80)]
    private string $base;

    #[ORM\Column(name: 'admin_siteaccess', length: 80, nullable: true)]
    private ?string $adminSiteaccess;

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
        $this->hosts = $site->hosts;
        $this->languages = $site->languages;
        $this->languageMode = $site->languageMode->value;
        $this->rootLocationId = $site->rootLocationId;
        $this->design = $site->design;
        $this->base = $site->base;
        $this->adminSiteaccess = $site->adminSiteaccess;
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
            $this->hosts,
            $this->languages,
            LanguageMode::from($this->languageMode),
            $this->rootLocationId,
            $this->design,
            $this->base,
            $this->adminSiteaccess,
        );
    }
}
