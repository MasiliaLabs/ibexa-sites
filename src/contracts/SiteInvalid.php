<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/** A site the registry refuses, with every reason at once (the form or the assistant shows them all). */
final class SiteInvalid extends \InvalidArgumentException
{
    /** @param list<string> $problems */
    public function __construct(public readonly array $problems)
    {
        parent::__construct(implode("\n", $problems));
    }
}
