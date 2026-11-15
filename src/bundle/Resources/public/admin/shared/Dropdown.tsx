import React, { useId } from 'react';

import { fromId, toId, toItems } from './adapters';
import type { Option } from './adapters';
import { describedBy, FieldFrame } from './Field';
import { DropdownSingleInput } from './ids';

interface DropdownFieldProps {
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
    /** An entry that stands for "nothing chosen" (its value is the empty string); without it the dropdown must have a value */
    emptyLabel?: string;
    /** What an unchosen required dropdown says */
    placeholder?: string;
    required?: boolean;
    hint?: string;
    error?: string;
    disabled?: boolean;
    hideLabel?: boolean;
}

/** A choice among a list, in the design system's dropdown: a field with its label, hint and error, and a search once the list is long. */
export function DropdownField({ label, value, options, onChange, emptyLabel, placeholder, required, hint, error, disabled, hideLabel }: DropdownFieldProps): React.ReactElement {
    const id = useId();

    return (
        <FieldFrame id={id} group labelHidden={hideLabel} label={label} required={required} hint={hint} error={error}>
            <div aria-describedby={describedBy(id, hint, error)}>
                <DropdownSingleInput
                    name={id}
                    items={toItems(options, emptyLabel)}
                    value={toId(value, emptyLabel !== undefined)}
                    onChange={(selected) => onChange(fromId(selected))}
                    disabled={disabled}
                    error={error !== undefined}
                    renderEmptySelectionInfo={placeholder === undefined ? undefined : () => <div className="ids-dropdown__placeholder">{placeholder}</div>}
                />
            </div>
        </FieldFrame>
    );
}
