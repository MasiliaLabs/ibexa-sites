<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

final class SiteNotFound extends \RuntimeException
{
    public function __construct(public readonly string $identifier)
    {
        parent::__construct(sprintf('There is no site "%s".', $identifier));
    }
}
