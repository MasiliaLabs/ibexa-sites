/**
 * What the scripts of the admin share with the page that carries them: the CSRF token of the admin (its CSRF-Token meta tag),
 * which comes back in the X-CSRF-Token header of every write.
 */

/** The CSRF token the admin page was given; empty when there is no page (tests, a page that is not the admin's). */
export function csrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    return document.querySelector<HTMLMetaElement>('meta[name="CSRF-Token"]')?.content ?? '';
}

/** The headers a request of a script of the admin carries: JSON both ways, and the token of the page. */
export function adminHeaders(extra: Record<string, string> = {}): Record<string, string> {
    return { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrfToken(), ...extra };
}
