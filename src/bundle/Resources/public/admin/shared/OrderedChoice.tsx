import React, { useId } from 'react';

import type { Option } from './adapters';
import { DropdownField } from './Dropdown';
import { FieldFrame } from './Field';
import { Button, Chip, Tag } from './ids';

interface OrderedChoiceProps {
    label: string;
    /** Every value that can be chosen */
    options: Option[];
    /** The chosen values, in order: the order matters (the first one is the main one) */
    value: string[];
    onChange: (value: string[]) => void;
    addPlaceholder: string;
    moveUpLabel: string;
    moveDownLabel: string;
    removeLabel: string;
    firstLabel?: string;
    required?: boolean;
    hint?: string;
    error?: string;
}

/** A short ordered list of choices as chips (each taken out with its cross, moved up or down with its arrows), and what is left to add. */
export function OrderedChoice({ label, options, value, onChange, addPlaceholder, moveUpLabel, moveDownLabel, removeLabel, firstLabel, required, hint, error }: OrderedChoiceProps): React.ReactElement {
    const id = useId();
    const labelOf = (v: string): string => options.find((o) => o.value === v)?.label ?? v;
    const remaining = options.filter((o) => !value.includes(o.value));
    const move = (index: number, by: number): void => {
        const next = [...value];
        const [item] = next.splice(index, 1);
        next.splice(index + by, 0, item);
        onChange(next);
    };

    return (
        <FieldFrame id={id} group label={label} required={required} hint={hint} error={error}>
            <ol className="masilia-kit-ordered" id={id}>
                {value.map((v, index) => (
                    <li className="masilia-kit-ordered__item" key={v}>
                        <Chip isDeletable onDelete={() => onChange(value.filter((x) => x !== v))}>{labelOf(v)}</Chip>
                        {index === 0 && firstLabel !== undefined ? <Tag type="info" size="small">{firstLabel}</Tag> : null}
                        <span className="masilia-kit-ordered__actions">
                            <Button type="tertiary" size="small" icon="caret-up" ariaLabel={`${moveUpLabel}: ${labelOf(v)}`} title={moveUpLabel} disabled={index === 0} onClick={() => move(index, -1)} />
                            <Button type="tertiary" size="small" icon="caret-down" ariaLabel={`${moveDownLabel}: ${labelOf(v)}`} title={moveDownLabel} disabled={index === value.length - 1} onClick={() => move(index, 1)} />
                        </span>
                    </li>
                ))}
            </ol>
            {remaining.length > 0 ? (
                <div className="masilia-kit-ordered__add">
                    <DropdownField label={addPlaceholder} value="" options={remaining} placeholder={addPlaceholder} hideLabel onChange={(added) => added !== '' && onChange([...value, added])} />
                </div>
            ) : null}
        </FieldFrame>
    );
}
