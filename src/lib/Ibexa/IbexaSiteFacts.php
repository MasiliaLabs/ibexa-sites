<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Ibexa;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\LanguageService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Masilia\IbexaSites\Validation\SiteFacts;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/** The facts of the validation, read from Ibexa and from the configuration the project compiled. */
readonly class IbexaSiteFacts implements SiteFacts
{
    /**
     * @param array<string, mixed>        $designs        ibexa.design.list
     * @param array<string, list<string>> $groups         ibexa.site_access.groups
     * @param list<string>                $siteaccesses   ibexa.site_access.list
     * @param array<string, mixed>        $matchConfig    ibexa.site_access.match_config
     */
    public function __construct(
        private LocationService $locations,
        private LanguageService $languages,
        private ConfigResolverInterface $config,
        #[Autowire('%ibexa.design.list%')]
        private array $designs,
        #[Autowire('%ibexa.site_access.groups%')]
        private array $groups,
        #[Autowire('%ibexa.site_access.list%')]
        private array $siteaccesses,
        #[Autowire('%ibexa.site_access.match_config%')]
        private array $matchConfig,
        #[Autowire('%admin_group_name%')]
        private string $adminGroup,
    ) {
    }

    public function locationExists(int $locationId): bool
    {
        try {
            $this->locations->loadLocation($locationId);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }

    public function languageExists(string $code): bool
    {
        try {
            $this->languages->loadLanguage($code);

            return true;
        } catch (NotFoundException) {
            return false;
        }
    }

    public function languages(): array
    {
        $codes = [];
        foreach ($this->languages->loadLanguages() as $language) {
            if ($language->enabled) {
                $codes[] = $language->languageCode;
            }
        }
        sort($codes);

        return $codes;
    }

    public function designs(): array
    {
        $names = array_map('strval', array_keys($this->designs));
        sort($names);

        return array_values(array_diff($names, ['admin']));
    }

    public function baseGroups(): array
    {
        $names = array_values(array_filter(array_map('strval', array_keys($this->groups)), fn (string $group): bool => $group !== $this->adminGroup));
        sort($names);

        return $names;
    }

    public function adminSiteaccesses(): array
    {
        return array_map('strval', $this->groups[$this->adminGroup] ?? []);
    }

    public function reservedNames(): array
    {
        return array_values(array_unique([...array_map('strval', $this->siteaccesses), ...array_map('strval', array_keys($this->groups)), ...$this->adminSiteaccesses()]));
    }

    public function staticHosts(): array
    {
        return StaticHosts::from($this->matchConfig);
    }

    public function staticRoots(): array
    {
        $roots = [];
        foreach ([...$this->reservedNames()] as $scope) {
            try {
                $roots[] = (int) $this->config->getParameter('content.tree_root.location_id', null, $scope);
            } catch (Throwable) {
                // A scope with no root of its own.
            }
        }

        return array_values(array_unique(array_filter($roots, static fn (int $id): bool => $id > 2)));
    }
}
