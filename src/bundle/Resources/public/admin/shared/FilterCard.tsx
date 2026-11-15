import React, { useId } from 'react';
import type { ReactNode } from 'react';

import type { Option } from './Field';

/** A bordered card that holds the filters of a list: its zones are told apart by a thin rule, as on the admin's list screens. */
export function FilterCard({ children }: { children: ReactNode }): React.ReactElement {
    return <div className="masilia-kit-filters" role="search">{children}</div>;
}

export function FilterZone({ children }: { children: ReactNode }): React.ReactElement {
    return <div className="masilia-kit-filters__zone">{children}</div>;
}

interface FilterSelectProps {
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}

/** A filter that is a choice: its label above, the list below in the style of the admin's fields. */
export function FilterSelect({ label, value, options, onChange }: FilterSelectProps): React.ReactElement {
    const id = useId();

    return (
        <div className="masilia-kit-filters__select">
            <label className="ibexa-label" htmlFor={id}>{label}</label>
            <select id={id} className="ibexa-input ibexa-input--text form-select masilia-kit-select" value={value} onChange={(event) => onChange(event.target.value)}>
                {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
        </div>
    );
}
