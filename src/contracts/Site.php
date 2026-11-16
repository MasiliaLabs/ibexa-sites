<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/**
 * A site as the registry keeps it, the way the project's YAML describes one: a siteaccess group that holds what the site shares
 * (the root of its tree, its design, the cookie of its session) and the siteaccesses of its languages, each with its own languages
 * and its own address.
 */
final readonly class Site
{
    /**
     * @param string                    $identifier  The name of the siteaccess group ("acme_group")
     * @param string|null               $base        A group the site takes the settings it does not set from (images, rich text, menus…), if any
     * @param string|null               $sessionCookieDomain
     * @param string|null               $varDir         The directory the files of the site live in, relative to the project ("var/demo"); the project's own when empty
     * @param string|null               $layoutRemoteId The remote id of the content that holds the layout of the site (logo, footer), for masilia/ibexa-blocks
     * @param list<SiteAccessDefinition> $siteaccesses      The front ones: what visitors see
     * @param list<SiteAccessDefinition> $adminSiteaccesses The ones editors work in (members of the admin group), same rules of address
     * @param string|null               $email   The contact address of the site (emails, footers), if any
     * @param string|null               $address The postal address of the site, if any
     */
    public function __construct(
        public string $identifier,
        public string $name,
        public SiteStatus $status,
        public int $rootLocationId,
        public string $design,
        public array $siteaccesses,
        public ?string $base = null,
        public ?string $sessionCookieDomain = null,
        public ?string $adminSiteaccess = null,
        public ?string $varDir = null,
        public ?string $layoutRemoteId = null,
        public array $adminSiteaccesses = [],
        public ?string $email = null,
        public ?string $address = null,
    ) {
    }

    /** @return list<string> */
    public function siteaccessNames(): array
    {
        return array_map(static fn (SiteAccessDefinition $s): string => $s->name, $this->siteaccesses);
    }

    /** @return list<SiteAccessDefinition> The front siteaccesses, then the admin ones */
    public function allSiteaccesses(): array
    {
        return [...$this->siteaccesses, ...$this->adminSiteaccesses];
    }

    /** @return list<string> */
    public function allSiteaccessNames(): array
    {
        return array_map(static fn (SiteAccessDefinition $s): string => $s->name, $this->allSiteaccesses());
    }

    public function isAdmin(string $siteaccess): bool
    {
        return in_array($siteaccess, array_map(static fn (SiteAccessDefinition $s): string => $s->name, $this->adminSiteaccesses), true);
    }

    /** A front or an admin siteaccess of the site */
    public function siteaccess(string $name): ?SiteAccessDefinition
    {
        foreach ($this->allSiteaccesses() as $siteaccess) {
            if ($siteaccess->name === $name) {
                return $siteaccess;
            }
        }

        return null;
    }

    /** @return list<string> The distinct hosts of its siteaccesses, fronts and admins */
    public function hosts(): array
    {
        return array_values(array_unique(array_map(static fn (SiteAccessDefinition $s): string => $s->host, $this->allSiteaccesses())));
    }

    /** @return list<string> The distinct languages of its siteaccesses, in order */
    public function languages(): array
    {
        $languages = [];
        foreach ($this->siteaccesses as $siteaccess) {
            foreach ($siteaccess->languages as $language) {
                $languages[$language] = $language;
            }
        }

        return array_values($languages);
    }

    public function withStatus(SiteStatus $status): self
    {
        return new self($this->identifier, $this->name, $status, $this->rootLocationId, $this->design, $this->siteaccesses, $this->base, $this->sessionCookieDomain, $this->adminSiteaccess, $this->varDir, $this->layoutRemoteId, $this->adminSiteaccesses, $this->email, $this->address);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier, 'name' => $this->name, 'status' => $this->status->value, 'rootLocationId' => $this->rootLocationId,
            'design' => $this->design, 'base' => $this->base, 'sessionCookieDomain' => $this->sessionCookieDomain, 'adminSiteaccess' => $this->adminSiteaccess,
            'varDir' => $this->varDir, 'layoutRemoteId' => $this->layoutRemoteId, 'email' => $this->email, 'address' => $this->address,
            'siteaccesses' => array_map(static fn (SiteAccessDefinition $s): array => $s->toArray(), $this->siteaccesses),
            'adminSiteaccesses' => array_map(static fn (SiteAccessDefinition $s): array => $s->toArray(), $this->adminSiteaccesses),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $optional = static fn (string $key): ?string => isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : null;

        return new self(
            (string) $data['identifier'],
            (string) $data['name'],
            SiteStatus::from((string) $data['status']),
            (int) $data['rootLocationId'],
            (string) $data['design'],
            array_map(static fn (array $row): SiteAccessDefinition => SiteAccessDefinition::fromArray($row), array_values((array) $data['siteaccesses'])),
            $optional('base'),
            $optional('sessionCookieDomain'),
            $optional('adminSiteaccess'),
            $optional('varDir'),
            $optional('layoutRemoteId'),
            array_map(static fn (array $row): SiteAccessDefinition => SiteAccessDefinition::fromArray($row), array_values((array) ($data['adminSiteaccesses'] ?? []))),
            $optional('email'),
            $optional('address'),
        );
    }
}
