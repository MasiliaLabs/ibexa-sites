import React from 'react';

import { IconSvg } from './IconSvg';

/** An address that opens in a new tab, with the small "open in a new tab" icon the admin puts next to its links. */
export function ExternalLink({ href, children, label }: { href: string; children: string; label: string }): React.ReactElement {
    return (
        <span className="masilia-kit-extlink">
            <span className="masilia-kit-extlink__text">{children}</span>
            <a href={href} target="_blank" rel="noreferrer" title={label} aria-label={`${label}: ${children}`}>
                <IconSvg name="open-newtab" />
            </a>
        </span>
    );
}
