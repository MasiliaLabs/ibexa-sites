<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Ibexa;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;

/** What a person needs to recognise a location: the name of its content, its type, and the names of the ones above it. */
readonly class LocationLookup
{
    public function __construct(private Repository $repository)
    {
    }

    /**
     * The content that has this remote id, told by its main location (the layout of a site is kept by remote id).
     *
     * @return array{id: int, name: string, path: list<string>, pathString: string, contentId: int, remoteId: string, contentType: string}|null
     */
    public function describeByRemoteId(string $remoteId): ?array
    {
        try {
            $info = $this->repository->getContentService()->loadContentInfoByRemoteId($remoteId);
        } catch (NotFoundException|UnauthorizedException) {
            return null;
        }

        return $info->mainLocationId === null ? null : $this->describe((int) $info->mainLocationId);
    }

    /**
     * Read with the rights of the user: a location they cannot see is as good as one that is not there.
     *
     * @return array{id: int, name: string, path: list<string>, pathString: string, contentId: int, remoteId: string, contentType: string}|null
     */
    public function describe(int $locationId): ?array
    {
        $locations = $this->repository->getLocationService();
        try {
            $location = $locations->loadLocation($locationId);
            $names = [];
            foreach (array_slice(array_filter(explode('/', $location->pathString), static fn (string $id): bool => $id !== ''), 1) as $id) {
                $names[] = $locations->loadLocation((int) $id)->getContentInfo()->name;
            }
            $info = $location->getContentInfo();
            $type = $this->repository->getContentTypeService()->loadContentType($info->contentTypeId)->identifier;
        } catch (NotFoundException|UnauthorizedException) {
            return null;
        }

        return [
            'id' => $location->id,
            'name' => $info->name,
            'path' => $names,
            'pathString' => $location->pathString,
            'contentId' => (int) $info->id,
            'remoteId' => $info->remoteId,
            'contentType' => $type,
        ];
    }
}
