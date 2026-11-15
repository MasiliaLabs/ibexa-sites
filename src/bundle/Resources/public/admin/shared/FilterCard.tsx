import React from 'react';
import type { ReactNode } from 'react';

import type { Option } from './adapters';
import { DropdownField } from './Dropdown';

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
    /** The entry that stands for "no filter" ("All") */
    emptyLabel: string;
    onChange: (value: string) => void;
}

/** A filter that is a choice: its label above, the design system's dropdown below. */
export function FilterSelect({ label, value, options, emptyLabel, onChange }: FilterSelectProps): React.ReactElement {
    return (
        <div className="masilia-kit-filters__select">
            <DropdownField label={label} value={value} options={options} emptyLabel={emptyLabel} onChange={onChange} />
        </div>
    );
}
