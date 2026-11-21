<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Access;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SiteAccessLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use Masilia\Contracts\IbexaSites\Site;
use Psr\Log\LoggerInterface;

/**
 * Lets visitors into the siteaccesses of the active sites. The Anonymous role limits user/login to a list of siteaccesses (the
 * CRC32 of their names), and a siteaccess that is not in it sends every visitor to the login page. So an active site is added to
 * that list, and a site that is no longer served is taken out, whoever makes the change (it is done with the rights of the system).
 * A role with no such list lets everyone in already: nothing is touched.
 */
readonly class AnonymousLoginAccess
{
    public function __construct(
        private Repository $repository,
        private string $role = 'Anonymous',
        private ?LoggerInterface $logger = null,
        private ?SiteAccessServiceInterface $siteAccesses = null,
    ) {
    }

    /**
     * Without the values that are not a siteaccess Ibexa knows: it refuses a list with one ("Limitations did not validate"), and a project
     * that is brought to the registry has such values (the siteaccesses of its old configuration, or of sites that are still drafts). They come
     * back when their site is activated.
     *
     * @param list<string> $values
     * @param list<string> $knownNames
     *
     * @return list<string>
     */
    public static function onlyKnown(array $values, array $knownNames): array
    {
        $known = array_map(static fn (string $name): string => sprintf('%u', crc32($name)), $knownNames);

        return array_values(array_filter($values, static fn (string $value): bool => in_array($value, $known, true)));
    }

    /**
     * What the list becomes: the values it holds, without the siteaccesses of a site that was served, with those of one that is.
     *
     * @param list<string> $existing
     *
     * @return list<string>
     */
    public static function values(array $existing, ?Site $before, ?Site $after): array
    {
        $remove = $before !== null && $before->status->isServed() ? self::codes($before) : [];
        $add = $after !== null && $after->status->isServed() ? self::codes($after) : [];

        return array_values(array_unique([...array_diff($existing, $remove), ...$add]));
    }

    /** @return list<string> */
    private static function codes(Site $site): array
    {
        return array_map(static fn (string $name): string => sprintf('%u', crc32($name)), $site->allSiteaccessNames());
    }

    public function sync(?Site $before, ?Site $after): void
    {
        $this->repository->sudo(function (Repository $repository) use ($before, $after): void {
            $roles = $repository->getRoleService();
            try {
                $role = $roles->loadRoleByIdentifier($this->role);
            } catch (NotFoundException) {
                return;
            }
            foreach ($role->getPolicies() as $policy) {
                if ($policy->module !== 'user' || $policy->function !== 'login') {
                    continue;
                }
                foreach ($policy->getLimitations() as $limitation) {
                    if ($limitation->getIdentifier() !== 'SiteAccess') {
                        continue;
                    }
                    $existing = array_map('strval', $limitation->limitationValues);
                    $values = self::values($existing, $before, $after);
                    if ($this->siteAccesses !== null) {
                        $values = self::onlyKnown($values, array_map(static fn ($siteAccess): string => $siteAccess->name, iterator_to_array($this->siteAccesses->getAll(), false)));
                    }
                    if ($values === $existing || (count($values) === count($existing) && array_diff($values, $existing) === [])) {
                        return;
                    }
                    // A draft left behind (a change that did not end) would refuse a new one: this list is the registry's, it starts over.
                    try {
                        $left = $roles->loadRoleDraftByRoleId($role->id);
                        $roles->deleteRoleDraft($left);
                        $this->logger?->warning('A draft of the role "{role}" was discarded to update its login list for the sites of the registry.', ['role' => $this->role, 'draft_id' => $left->id]);
                    } catch (NotFoundException) {
                        // No draft: the normal case.
                    }
                    $draft = $roles->createRoleDraft($role);
                    foreach ($draft->getPolicies() as $draftPolicy) {
                        if ($draftPolicy instanceof PolicyDraft && $draftPolicy->module === 'user' && $draftPolicy->function === 'login') {
                            $update = $roles->newPolicyUpdateStruct();
                            $update->addLimitation(new SiteAccessLimitation(['limitationValues' => $values]));
                            $roles->updatePolicyByRoleDraft($draft, $draftPolicy, $update);
                        }
                    }
                    $roles->publishRoleDraft($draft);

                    return;
                }
            }
        });
    }
}
