import React, { useEffect, useId, useRef } from 'react';
import type { ReactNode } from 'react';

import { Button } from './ids';

const FOCUSABLE = 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/** The open dialogs, the last one on top: only that one answers the keyboard. */
const stack: symbol[] = [];

/** The Universal Discovery Widget has its own keyboard: while it is open (it is drawn over a dialog) a dialog leaves the keys to it. */
function browserOpen(): boolean {
    return document.querySelector('#react-udw .m-ud') !== null;
}

interface DialogProps {
    title: string;
    /** `panel` slides in from the right (forms); `center` is a small confirmation. */
    variant: 'panel' | 'center';
    onClose: () => void;
    children: ReactNode;
    footer?: ReactNode;
    role?: 'dialog' | 'alertdialog';
    closeLabel?: string;
    /** The id of the element that says what the dialog asks (for assistive technology) */
    describedBy?: string;
}

/** A modal dialog: Escape closes it, Tab stays inside, and the focus goes back to what opened it. */
export function Dialog({ title, variant, onClose, children, footer, role = 'dialog', closeLabel = 'Close', describedBy }: DialogProps): React.ReactElement {
    const ref = useRef<HTMLDivElement>(null);
    const titleId = useId();
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        const returnTo = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        // The field marked data-autofocus first, then the first field, then the first control.
        const target = ref.current?.querySelector<HTMLElement>('[data-autofocus]')
            ?? ref.current?.querySelector<HTMLElement>('input:not([disabled]), select:not([disabled]), textarea:not([disabled])')
            ?? ref.current?.querySelector<HTMLElement>(FOCUSABLE);
        target?.focus();

        const me = Symbol('dialog');
        stack.push(me);

        const onKeyDown = (event: KeyboardEvent): void => {
            if (stack[stack.length - 1] !== me || browserOpen()) {
                return;
            }
            if (event.key === 'Escape') {
                // Escape belongs to an open dropdown first: it closes the list, not the dialog behind it.
                if (document.activeElement instanceof Element && document.activeElement.closest('.ids-dropdown') !== null) {
                    return;
                }
                event.preventDefault();
                onCloseRef.current();

                return;
            }
            if (event.key !== 'Tab' || ref.current === null) {
                return;
            }
            const focusable = Array.from(ref.current.querySelectorAll<HTMLElement>(FOCUSABLE));
            if (focusable.length === 0) {
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const active = document.activeElement;
            if (!ref.current.contains(active)) {
                event.preventDefault();
                first.focus();
            } else if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            stack.splice(stack.indexOf(me), 1);
            returnTo?.focus();
        };
    }, []);

    return (
        <>
            <div className="masilia-kit-overlay" onClick={() => onClose()} />
            <div ref={ref} className={`masilia-kit-dialog masilia-kit-dialog--${variant}`} role={role} aria-modal="true" aria-labelledby={titleId} aria-describedby={describedBy}>
                <header className="masilia-kit-dialog__header">
                    <h2 className="masilia-kit-dialog__title" id={titleId}>{title}</h2>
                    <Button type="tertiary" icon="discard" ariaLabel={closeLabel} title={closeLabel} onClick={onClose} />
                </header>
                <div className="masilia-kit-dialog__body">{children}</div>
                {footer !== undefined ? <footer className="masilia-kit-dialog__footer">{footer}</footer> : null}
            </div>
        </>
    );
}
