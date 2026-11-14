<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Access;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SiteAccessLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Masilia\Contracts\IbexaSites\Site;

/**
 * Lets visitors into the siteaccesses of the active sites. The Anonymous role limits user/login to a list of siteaccesses (the
 * CRC32 of their names), and a siteaccess that is not in it sends every visitor to the login page. So an active site is added to
 * that list, and a site that is no longer served is taken out, whoever makes the change (it is done with the rights of the system).
 * A role with no such list lets everyone in already: nothing is touched.
 */
readonly class AnonymousLoginAccess
{
    public function __construct(private Repository $repository, private string $role = 'Anonymous')
    {
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
        return array_map(static fn (string $name): string => sprintf('%u', crc32($name)), $site->siteaccessNames());
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
                    if ($values === $existing || (count($values) === count($existing) && array_diff($values, $existing) === [])) {
                        return;
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
