import React from 'react';

/** The admin's spinner (its own animation), for what is loading or saving. */
export function Spinner({ label }: { label: string }): React.ReactElement {
    return <span className="masilia-kit-spinner" role="status" aria-label={label} />;
}
