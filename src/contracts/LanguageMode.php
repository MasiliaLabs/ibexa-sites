<?php

declare(strict_types=1);

namespace Masilia\Contracts\IbexaSites;

/** How the languages of a site are told apart in its addresses. */
enum LanguageMode: string
{
    /** One host: the first language is at the root, each other one under a prefix (/fr). */
    case Prefix = 'prefix';
    /** One host per language, in the order of the languages. */
    case Host = 'host';
}
