<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Persistence\Entity;

use Doctrine\ORM\Mapping as ORM;

/** A platform setting (table masilia_setting): a name and a value. */
#[ORM\Entity]
#[ORM\Table(name: 'masilia_setting')]
class SettingRecord
{
    #[ORM\Id]
    #[ORM\Column(length: 60)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function change(string $value): void
    {
        $this->value = $value;
    }
}
