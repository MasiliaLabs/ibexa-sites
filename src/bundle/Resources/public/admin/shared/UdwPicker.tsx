import React, { useEffect, useId, useRef, useState } from 'react';

import { Button } from './ids';
import { FieldFrame } from './Field';
import { openUdw, udwHost } from './udw';
import type { PickedLocation } from './udw';

/** What the picker shows of what was taken: its name and where it is. */
export interface PickedView {
    id: number;
    name: string;
    /** The names above it, from the top of the tree */
    path: string[];
}

interface UdwPickerProps {
    label: string;
    /** The configuration of the page for the widget (ibexa_udw_config) */
    config: Record<string, unknown>;
    /** Title of the widget */
    title: string;
    value: PickedView | null;
    onPick: (picked: PickedLocation) => void;
    onClear?: () => void;
    chooseLabel: string;
    changeLabel: string;
    clearLabel: string;
    emptyLabel: string;
    unavailableLabel: string;
    required?: boolean;
    hint?: string;
    error?: string;
    disabled?: boolean;
}

/** A location chosen by browsing the content tree with the admin's Universal Discovery Widget, shown by name and path. */
export function UdwPicker({ label, config, title, value, onPick, onClear, chooseLabel, changeLabel, clearLabel, emptyLabel, unavailableLabel, required, hint, error, disabled }: UdwPickerProps): React.ReactElement {
    const id = useId();
    const [unavailable, setUnavailable] = useState(false);
    const close = useRef<(() => void) | null>(null);

    // The widget is drawn by the page, not by this field: it goes when the field does.
    useEffect(() => () => close.current?.(), []);

    const open = (): void => {
        const host = udwHost();
        if (host === null) {
            setUnavailable(true);

            return;
        }
        close.current?.();
        close.current = openUdw(host, { config, title, startingLocationId: value !== null && value.id > 0 ? value.id : undefined, onConfirm: onPick }, (type, props) => React.createElement(type as React.ComponentType<Record<string, unknown>>, props));
    };

    return (
        <FieldFrame id={id} group label={label} required={required} hint={unavailable ? undefined : hint} error={unavailable ? unavailableLabel : error}>
            <div className="masilia-kit-picker" id={id}>
                <div className={`masilia-kit-picker__value${value === null ? ' masilia-kit-picker__value--empty' : ''}`} aria-live="polite">
                    {value === null ? emptyLabel : (
                        <>
                            <strong>{value.name}</strong>
                            <span className="masilia-kit-picker__path">{value.path.join(' › ')}</span>
                        </>
                    )}
                </div>
                <div className="masilia-kit-picker__actions">
                    <Button type="secondary" size="small" icon="content-tree" disabled={disabled} onClick={open}>{value === null ? chooseLabel : changeLabel}</Button>
                    {value !== null && onClear !== undefined ? <Button type="tertiary" size="small" icon="discard" ariaLabel={clearLabel} title={clearLabel} disabled={disabled} onClick={onClear} /> : null}
                </div>
            </div>
        </FieldFrame>
    );
}
