import React from 'react';

export type ChipTone = 'success' | 'warning' | 'neutral' | 'danger' | 'info';

/** A small pill that says a state: coloured with the admin's own palette, never by colour alone (it has its text). */
export function StatusChip({ tone, children }: { tone: ChipTone; children: string }): React.ReactElement {
    return <span className={`masilia-kit-chip masilia-kit-chip--${tone}`}>{children}</span>;
}
