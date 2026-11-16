import { shortCode } from './addresses';
import { emptyRow } from './rows';
import type { SiteaccessDraft } from './types';

/** An identifier from a name: lower case letters, digits and "_", ending in "_group", at most 30 characters ("Acme Shop" → "acme_group"). */
export function suggestIdentifier(name: string): string {
    const slug = name
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .replace(/^[0-9_]+/, '')
        .slice(0, 24)
        .replace(/_+$/g, '');

    return slug === '' ? '' : `${slug}_group`;
}

/** The stem of the siteaccess names of a site: its identifier without "_group". */
export function stem(identifier: string): string {
    return identifier.replace(/_group$/, '');
}

/** The name of the siteaccess for a language: the stem for the first, the stem and the short code for the others ("demo", "demo_fr"). */
export function suggestSiteaccessName(identifier: string, language: string, first: boolean): string {
    const base = stem(identifier);

    return first ? base : `${base}_${shortCode(language)}`;
}

/**
 * The rows of a site in several languages under one host: the first language at the root of the host, each other one under its
 * prefix, with the first language as the fallback of the others.
 */
export function suggestSiteaccesses(identifier: string, languages: readonly string[], host: string): SiteaccessDraft[] {
    return languages.map((language, index) => emptyRow({
        name: suggestSiteaccessName(identifier, language, index === 0),
        languages: index === 0 ? [language] : [language, languages[0]],
        host,
        uriPrefix: index === 0 ? '' : shortCode(language),
    }));
}

/**
 * The cookie domain that covers all the hosts of a site and no more than it needs: their longest common suffix, with its dot
 * (".demo.example.org"), or null when they share less than two labels.
 */
export function commonCookieDomain(hosts: readonly string[]): string | null {
    const lists = hosts.map((h) => h.trim().toLowerCase()).filter((h) => h !== '').map((h) => h.split('.').reverse());
    if (lists.length === 0) {
        return null;
    }
    const common: string[] = [];
    for (let i = 0; i < lists[0].length; i += 1) {
        if (!lists.every((labels) => labels[i] === lists[0][i])) {
            break;
        }
        common.push(lists[0][i]);
    }

    return common.length >= 2 ? `.${[...common].reverse().join('.')}` : null;
}

/** The language a new site starts with: English when Ibexa has it (the usual main language), otherwise the first one. */
export function initialLanguages(languages: readonly string[]): string[] {
    const first = languages.find((code) => code.toLowerCase().startsWith('eng')) ?? languages[0];

    return first === undefined ? [] : [first];
}
