import React from 'react';
import type { ReactNode } from 'react';

import { Accordion } from './ids';

interface SectionProps {
    title: string;
    open: boolean;
    children: ReactNode;
}

/** A section of a form: the design system's accordion, open or closed as the form says (and the person can still toggle it). */
export function Section({ title, open, children }: SectionProps): React.ReactElement {
    return (
        <div className="masilia-kit-section">
            <Accordion header={title} initiallyExpanded={open}>
                <div className="masilia-kit-section__body">{children}</div>
            </Accordion>
        </div>
    );
}
