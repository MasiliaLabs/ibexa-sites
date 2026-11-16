<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/**
 * One siteaccess of a site, as the project's own YAML has them ("acme_fr"): the languages it serves (the first one is its
 * language, the others the ones content falls back to) and the address it answers on, a host and, when the host holds several
 * siteaccesses, the prefix of the path that tells them apart ("fr" for /fr). Without a prefix it is the one at the root of the host.
 */
final readonly class SiteAccessDefinition
{
    /**
     * @param list<string> $languages Ibexa language codes ("fre-FR"), the first is the siteaccess' own
     */
    public function __construct(
        public string $name,
        public array $languages,
        public string $host,
        public ?string $uriPrefix = null,
    ) {
    }

    /** @return array{name: string, languages: list<string>, host: string, uriPrefix: ?string} */
    public function toArray(): array
    {
        return ['name' => $this->name, 'languages' => $this->languages, 'host' => $this->host, 'uriPrefix' => $this->uriPrefix];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (string) $data['name'],
            array_map('strval', (array) $data['languages']),
            (string) $data['host'],
            isset($data['uriPrefix']) && $data['uriPrefix'] !== '' ? (string) $data['uriPrefix'] : null,
        );
    }
}
