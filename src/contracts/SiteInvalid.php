<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/** A site the registry refuses, with every reason at once (the form or the assistant shows them all). */
final class SiteInvalid extends \InvalidArgumentException
{
    /**
     * @param list<string>  $problems The reasons, as sentences
     * @param list<Problem> $items    The same reasons with the field each is about (empty when the refusal is not about a field)
     */
    public function __construct(public readonly array $problems, public readonly array $items = [])
    {
        parent::__construct(implode("\n", $problems));
    }

    /** @param list<Problem> $items */
    public static function fromProblems(array $items): self
    {
        return new self(array_map(static fn (Problem $p): string => $p->message, $items), $items);
    }
}
