import React from 'react';

import { tagKind } from './adapters';
import { Tag } from './ids';

/** The status of a site as the design system's tag (its own colours), with the text of the status. */
export function StatusTag({ status, children }: { status: 'draft' | 'active' | 'disabled'; children: string }): React.ReactElement {
    return <Tag type={tagKind(status)} size="small">{children}</Tag>;
}
