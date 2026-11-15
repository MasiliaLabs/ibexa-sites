import type { SiteaccessDraft } from './types';

/** The short code of a language as a site's address carries it ("fre-FR" is "fr"): the same table as the server's. */
const SHORT: Record<string, string> = {
    eng: 'en', fre: 'fr', ara: 'ar', ger: 'de', spa: 'es', ita: 'it', por: 'pt', dut: 'nl', rus: 'ru', chi: 'zh', jpn: 'ja', tur: 'tr',
    pol: 'pl', swe: 'sv', dan: 'da', nor: 'no', fin: 'fi', gre: 'el', heb: 'he', hin: 'hi', kor: 'ko', cze: 'cs', hun: 'hu', rum: 'ro',
};

export function shortCode(language: string): string {
    const three = language.split('-')[0].toLowerCase();

    return SHORT[three] ?? three;
}

/** What a visitor types for a siteaccess: its host, and its prefix when it has one. Empty while there is no host. */
export function addressOf(row: Pick<SiteaccessDraft, 'host' | 'uriPrefix'>, scheme = 'https'): string {
    const host = row.host.trim().toLowerCase();
    if (host === '') {
        return '';
    }
    const prefix = row.uriPrefix.trim().replace(/^\/+|\/+$/g, '').toLowerCase();

    return `${scheme}://${host}/${prefix}`.replace(/\/$/, '/');
}

export interface AddressLine {
    key: string;
    name: string;
    languages: string[];
    url: string;
}

/** The addresses a site would answer on, one line per siteaccess that has a host (what the form shows next to the rows). */
export function addressLines(rows: readonly SiteaccessDraft[], scheme = 'https'): AddressLine[] {
    return rows.flatMap((row): AddressLine[] => {
        const url = addressOf(row, scheme);

        return url === '' ? [] : [{ key: row.key, name: row.name, languages: row.languages, url }];
    });
}
