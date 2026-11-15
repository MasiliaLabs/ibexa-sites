<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/**
 * One reason a site is refused, with the field it is about: "name", "rootLocationId", "siteaccesses[1].host"… (the shape of
 * {@see Site::toArray()}), so that a form can show it under the field. The message is for a person, in English.
 */
final readonly class Problem
{
    public function __construct(public string $path, public string $message)
    {
    }
}
