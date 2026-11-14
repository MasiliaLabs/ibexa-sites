<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Ibexa;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Masilia\Contracts\IbexaSites\Site;
use Masilia\Contracts\IbexaSites\SiteAccessDefinition;
use Masilia\Contracts\IbexaSites\SiteStatus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

/**
 * The sites the project declares in its YAML, described the way the registry keeps them: one per siteaccess group, with what the
 * configuration resolves for its siteaccesses in this environment (the hosts come from the environment variables).
 */
readonly class StaticSites
{
    /**
     * @param array<string, list<string>> $groups      ibexa.site_access.groups
     * @param array<string, mixed>        $matchConfig ibexa.site_access.match_config
     */
    public function __construct(
        private ConfigResolverInterface $config,
        #[Autowire('%ibexa.site_access.groups%')]
        private array $groups,
        #[Autowire('%ibexa.site_access.match_config%')]
        private array $matchConfig,
        #[Autowire('%admin_group_name%')]
        private string $adminGroup,
    ) {
    }

    /**
     * @param list<string> $skipped Filled with what could not be described, and why
     *
     * @return list<Site> Drafts, named after their group
     */
    public function all(array &$skipped = []): array
    {
        $addresses = StaticAddresses::from($this->matchConfig);
        $sites = [];
        foreach ($this->groups as $group => $members) {
            $members = array_values(array_unique($members));
            if ($group === 'front_group' || $group === $this->adminGroup || $members === []) {
                continue;
            }
            $definitions = [];
            foreach ($members as $member) {
                if (!isset($addresses[$member])) {
                    $skipped[] = sprintf('%s: the siteaccess "%s" has no host in siteaccess.match.', $group, $member);
                    continue 2;
                }
                $languages = $this->value('languages', $member);
                if (!is_array($languages) || $languages === []) {
                    $skipped[] = sprintf('%s: the siteaccess "%s" has no languages.', $group, $member);
                    continue 2;
                }
                $definitions[] = new SiteAccessDefinition($member, array_values(array_map('strval', $languages)), $addresses[$member]['host'], $addresses[$member]['prefix']);
            }
            $root = $this->value('content.tree_root.location_id', $members[0]);
            $design = $this->value('design', $members[0]);
            if (!is_int($root) && !ctype_digit((string) $root) || !is_string($design)) {
                $skipped[] = sprintf('%s: no root location or no design.', $group);
                continue;
            }
            $cookie = $this->value('session.cookie_domain', $members[0]);
            $sites[] = new Site($group, $group, SiteStatus::Draft, (int) $root, $design, $definitions, null, is_string($cookie) && $cookie !== '' ? $cookie : null);
        }

        return $sites;
    }

    private function value(string $name, string $scope): mixed
    {
        try {
            return $this->config->hasParameter($name, null, $scope) ? $this->config->getParameter($name, null, $scope) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
