import React, { useId } from 'react';

import { Button } from './ids';
import { FieldFrame } from './Field';
import type { Option } from './Field';

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

/** A short ordered list of choices: each can be moved up or down or taken out, and what is left is offered to add. */
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
                        <span className="masilia-kit-ordered__label">{labelOf(v)}</span>
                        {index === 0 && firstLabel !== undefined ? <span className="masilia-kit-chip masilia-kit-chip--info">{firstLabel}</span> : null}
                        <span className="masilia-kit-ordered__actions">
                            <Button type="tertiary" size="small" icon="caret-up" ariaLabel={`${moveUpLabel}: ${labelOf(v)}`} title={moveUpLabel} disabled={index === 0} onClick={() => move(index, -1)} />
                            <Button type="tertiary" size="small" icon="caret-down" ariaLabel={`${moveDownLabel}: ${labelOf(v)}`} title={moveDownLabel} disabled={index === value.length - 1} onClick={() => move(index, 1)} />
                            <Button type="tertiary" size="small" icon="discard" ariaLabel={`${removeLabel}: ${labelOf(v)}`} title={removeLabel} onClick={() => onChange(value.filter((x) => x !== v))} />
                        </span>
                    </li>
                ))}
            </ol>
            {remaining.length > 0 ? (
                <select
                    className="ibexa-input ibexa-input--text form-select masilia-kit-select masilia-kit-ordered__add"
                    value=""
                    aria-label={addPlaceholder}
                    onChange={(event) => event.target.value !== '' && onChange([...value, event.target.value])}
                >
                    <option value="">{addPlaceholder}</option>
                    {remaining.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
            ) : null}
        </FieldFrame>
    );
}
