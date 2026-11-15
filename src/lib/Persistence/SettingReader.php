<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Masilia\IbexaSites\Persistence\Entity\SettingRecord;

/** Reads a platform setting from the database, and nothing else. */
readonly class SettingReader
{
    public function __construct(private EntityManagerInterface $entities)
    {
    }

    public function value(string $name): ?string
    {
        return $this->entities->getRepository(SettingRecord::class)->find($name)?->value();
    }
}
