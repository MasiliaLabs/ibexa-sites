import React from 'react';

import { iconPath } from './icons';

type IconSize = 'small' | 'small-medium' | 'medium';

/** An icon of the admin's sprite, drawn with its own classes (same size and colour rules as the icons of its screens). */
export function IconSvg({ name, size = 'small-medium', className = '' }: { name: string; size?: IconSize; className?: string }): React.ReactElement {
    return (
        <svg className={`ibexa-icon ibexa-icon--${size} ${className}`.trim()} aria-hidden="true" focusable="false">
            <use xlinkHref={iconPath(name)} />
        </svg>
    );
}
