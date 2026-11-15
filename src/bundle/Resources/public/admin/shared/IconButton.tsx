import React from 'react';

import { IconSvg } from './IconSvg';

interface IconButtonProps {
    icon: string;
    /** What the button does: its tooltip and its name for assistive technology */
    label: string;
    onClick?: () => void;
    href?: string;
    disabled?: boolean;
}

/** A ghost button with an icon and no text, as the tables of the admin have for their actions. */
export function IconButton({ icon, label, onClick, href, disabled }: IconButtonProps): React.ReactElement {
    const className = 'btn ibexa-btn ibexa-btn--ghost ibexa-btn--no-text';
    const content = <IconSvg name={icon} />;

    return href !== undefined && disabled !== true
        ? <a className={className} href={href} title={label} aria-label={label}>{content}</a>
        : <button type="button" className={className} title={label} aria-label={label} disabled={disabled} onClick={onClick}>{content}</button>;
}
