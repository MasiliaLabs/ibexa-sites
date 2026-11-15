export type NoticeKind = 'success' | 'error' | 'info';

/** Shows a toast through Ibexa's own notification system. */
export function notify(kind: NoticeKind, message: string): void {
    document.body.dispatchEvent(new CustomEvent('ibexa-notify', { detail: { message, label: kind } }));
}
