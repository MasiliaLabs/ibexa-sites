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
     * @param string                    $identifier  The name of the siteaccess group ("fossil_exit_group")
     * @param string|null               $base        A group the site takes the settings it does not set from (images, rich text, menus…), if any
     * @param string|null               $sessionCookieDomain
     * @param list<SiteAccessDefinition> $siteaccesses
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
    ) {
    }

    /** @return list<string> */
    public function siteaccessNames(): array
    {
        return array_map(static fn (SiteAccessDefinition $s): string => $s->name, $this->siteaccesses);
    }

    public function siteaccess(string $name): ?SiteAccessDefinition
    {
        foreach ($this->siteaccesses as $siteaccess) {
            if ($siteaccess->name === $name) {
                return $siteaccess;
            }
        }

        return null;
    }

    /** @return list<string> The distinct hosts of its siteaccesses */
    public function hosts(): array
    {
        return array_values(array_unique(array_map(static fn (SiteAccessDefinition $s): string => $s->host, $this->siteaccesses)));
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
        return new self($this->identifier, $this->name, $status, $this->rootLocationId, $this->design, $this->siteaccesses, $this->base, $this->sessionCookieDomain, $this->adminSiteaccess);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier, 'name' => $this->name, 'status' => $this->status->value, 'rootLocationId' => $this->rootLocationId,
            'design' => $this->design, 'base' => $this->base, 'sessionCookieDomain' => $this->sessionCookieDomain, 'adminSiteaccess' => $this->adminSiteaccess,
            'siteaccesses' => array_map(static fn (SiteAccessDefinition $s): array => $s->toArray(), $this->siteaccesses),
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
        );
    }
}
