import React from 'react';
import type { ReactNode } from 'react';

import { Dialog } from './Dialog';
import { Button } from './ids';

interface ConfirmDialogProps {
    title: string;
    /** What the action does, in a sentence or a few lines */
    children: ReactNode;
    confirmLabel: string;
    cancelLabel: string;
    /** A destructive action: the confirm button is drawn as a warning */
    danger?: boolean;
    busy?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}

/** Asks before something that is not undone at a click: says what it does, and has Cancel next to the action. */
export function ConfirmDialog({ title, children, confirmLabel, cancelLabel, danger, busy, onConfirm, onCancel }: ConfirmDialogProps): React.ReactElement {
    return (
        <Dialog
            title={title}
            variant="center"
            role="alertdialog"
            onClose={onCancel}
            footer={(
                <>
                    <Button type="tertiary" onClick={onCancel}>{cancelLabel}</Button>
                    <span data-autofocus="" tabIndex={-1} />
                    <Button type="primary" disabled={busy === true} onClick={onConfirm}>{danger === true ? `⚠ ${confirmLabel}` : confirmLabel}</Button>
                </>
            )}
        >
            <div className="masilia-kit-dialog__text">{children}</div>
        </Dialog>
    );
}
