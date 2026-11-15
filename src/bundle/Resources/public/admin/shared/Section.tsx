import React, { useEffect, useState } from 'react';
import type { ReactNode } from 'react';

import { Accordion } from './ids';

interface SectionProps {
    title: string;
    open: boolean;
    children: ReactNode;
}

/**
 * A section of a form: the design system's accordion. The form can open it (it holds something, or a problem to show) but never
 * closes it by itself: a section that shuts under the cursor takes its field, and the focus, with it. The person closes it.
 */
export function Section({ title, open, children }: SectionProps): React.ReactElement {
    const [latched, setLatched] = useState(open);

    useEffect(() => {
        if (open) {
            setLatched(true);
        }
    }, [open]);

    return (
        <div className="masilia-kit-section">
            <Accordion header={title} initiallyExpanded={latched}>
                <div className="masilia-kit-section__body">{children}</div>
            </Accordion>
        </div>
    );
}
