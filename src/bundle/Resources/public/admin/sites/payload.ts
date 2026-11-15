import { emptyRow } from './rows';
import type { SiteDraft, SiteDto, SiteaccessDraft, SiteaccessDto } from './types';

export function emptyDraft(): SiteDraft {
    return {
        identifier: '', name: '', rootLocationId: null, design: '', base: '', sessionCookieDomain: '', adminSiteaccess: '', varDir: '', layoutRemoteId: '', email: '', address: '',
        siteaccesses: [], adminSiteaccesses: [],
    };
}

function rowFrom(dto: SiteaccessDto): SiteaccessDraft {
    return emptyRow({ name: dto.name, languages: [...dto.languages], host: dto.host, uriPrefix: dto.uriPrefix ?? '' });
}

/** The form's state for a site of the server: empty values as empty text, every row with a key of its own. */
export function draftFrom(site: SiteDto): SiteDraft {
    return {
        identifier: site.identifier,
        name: site.name,
        rootLocationId: site.rootLocationId,
        design: site.design,
        base: site.base ?? '',
        sessionCookieDomain: site.sessionCookieDomain ?? '',
        adminSiteaccess: site.adminSiteaccess ?? '',
        varDir: site.varDir ?? '',
        layoutRemoteId: site.layoutRemoteId ?? '',
        email: site.email ?? '',
        address: site.address ?? '',
        siteaccesses: site.siteaccesses.map(rowFrom),
        adminSiteaccesses: site.adminSiteaccesses.map(rowFrom),
    };
}

function rowPayload(row: SiteaccessDraft): Record<string, unknown> {
    return { name: row.name, languages: row.languages, host: row.host, uriPrefix: row.uriPrefix };
}

/**
 * What is sent to the server: the fields as they are written (the server cleans them and says what is wrong), without the keys
 * of the rows, and without a status (the server decides it).
 */
export function toPayload(draft: SiteDraft): Record<string, unknown> {
    return {
        identifier: draft.identifier,
        name: draft.name,
        rootLocationId: draft.rootLocationId ?? 0,
        design: draft.design,
        base: draft.base,
        sessionCookieDomain: draft.sessionCookieDomain,
        adminSiteaccess: draft.adminSiteaccess,
        varDir: draft.varDir,
        layoutRemoteId: draft.layoutRemoteId,
        email: draft.email,
        address: draft.address,
        siteaccesses: draft.siteaccesses.map(rowPayload),
        adminSiteaccesses: draft.adminSiteaccesses.map(rowPayload),
    };
}

/** Whether the form differs from the site it was made from (to warn before leaving, and to enable Save). */
export function isChanged(draft: SiteDraft, site: SiteDto | null): boolean {
    return JSON.stringify(toPayload(draft)) !== JSON.stringify(toPayload(site === null ? emptyDraft() : draftFrom(site)));
}
