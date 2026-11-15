import { messageFor } from '@masilia-admin-kit/http';
import type { FieldProblem } from '@masilia-admin-kit/http';
import type { Translate } from '@masilia-admin-kit/i18n';
import type { Options, SiteDraft, SiteDto, SiteaccessDraft } from './types';

// The rules of the server's SiteValidator for what a form can see for itself. The server checks again and has the last word:
// what only it knows (Ibexa's languages and locations, the YAML, the database) comes back from /validate.
const IDENTIFIER = /^[a-z][a-z0-9_]{1,29}$/;
const SITEACCESS = /^[a-z][a-z0-9_]{1,39}$/;
const PREFIX = /^[a-z0-9][a-z0-9-]{0,29}$/;
const HOST = /^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/;
const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const COOKIE_DOMAIN = /^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/;
const VAR_DIR = /^[a-z0-9][a-z0-9_-]*(\/[a-z0-9][a-z0-9_-]*)*$/;
const REMOTE_ID = /^[A-Za-z0-9_-]{1,100}$/;

export interface ValidationContext {
    /** A new site (its identifier must be free) or a change */
    isNew: boolean;
    /** The sites of the registry other than this one */
    others: readonly SiteDto[];
    options: Options;
}

/** What the server will make of a host: trimmed, lower case. */
export function normalizedHost(host: string): string {
    return host.trim().toLowerCase();
}

/** What the server will make of a prefix: trimmed, without slashes, lower case. */
export function normalizedPrefix(prefix: string): string {
    return prefix.trim().replace(/^[\s/]+|[\s/]+$/g, '').toLowerCase();
}

function addressKey(row: Pick<SiteaccessDraft, 'host' | 'uriPrefix'>): string {
    return `${normalizedHost(row.host)}/${normalizedPrefix(row.uriPrefix)}`;
}

/** Every siteaccess of the sites of the registry, the other way a name or an address can be taken. */
function taken(others: readonly SiteDto[]): { names: Set<string>; addresses: Set<string> } {
    const names = new Set<string>();
    const addresses = new Set<string>();
    for (const site of others) {
        names.add(site.identifier);
        for (const sa of [...site.siteaccesses, ...site.adminSiteaccesses]) {
            names.add(sa.name);
            addresses.add(`${sa.host}/${sa.uriPrefix ?? ''}`);
        }
    }

    return { names, addresses };
}

function checkRow(path: string, row: SiteaccessDraft, draft: SiteDraft, ctx: ValidationContext, takenBy: ReturnType<typeof taken>, t: Translate, out: FieldProblem[]): void {
    const name = row.name.trim();
    const host = normalizedHost(row.host);
    const prefix = normalizedPrefix(row.uriPrefix);
    const all = [...draft.siteaccesses, ...draft.adminSiteaccesses];

    if (!SITEACCESS.test(name)) {
        out.push({ path: `${path}.name`, message: t('validation.sa.name') });
    } else if (all.filter((other) => other.name.trim() === name).length > 1) {
        out.push({ path: `${path}.name`, message: t('validation.sa.name.twice') });
    } else if (name === draft.identifier.trim() || takenBy.names.has(name)) {
        out.push({ path: `${path}.name`, message: t('validation.sa.name.taken') });
    }
    if (!HOST.test(host)) {
        out.push({ path: `${path}.host`, message: t('validation.sa.host') });
    } else {
        const address = `${host}/${prefix}`;
        if (all.filter((other) => addressKey(other) === address).length > 1) {
            out.push({ path: `${path}.host`, message: t('validation.sa.address.twice') });
        } else if (takenBy.addresses.has(address)) {
            out.push({ path: `${path}.host`, message: t('validation.sa.address.taken') });
        }
    }
    if (prefix !== '' && !PREFIX.test(prefix)) {
        out.push({ path: `${path}.uriPrefix`, message: t('validation.sa.prefix') });
    }
    // Only one siteaccess is at the root of a host: the first keeps it, the others are told.
    const atRoot = all.filter((other) => normalizedHost(other.host) === host && normalizedPrefix(other.uriPrefix) === '');
    if (prefix === '' && HOST.test(host) && atRoot.length > 1 && atRoot[0] !== row) {
        out.push({ path: `${path}.uriPrefix`, message: t('validation.sa.bare') });
    }
    if (row.languages.length === 0) {
        out.push({ path: `${path}.languages`, message: t('validation.sa.languages.none') });
    } else if (new Set(row.languages).size !== row.languages.length) {
        out.push({ path: `${path}.languages`, message: t('validation.sa.languages.twice') });
    } else {
        const unknown = row.languages.find((language) => ctx.options.languages.length > 0 && !ctx.options.languages.includes(language));
        if (unknown !== undefined) {
            out.push({ path: `${path}.languages`, message: t('validation.sa.languages.unknown', { language: unknown }) });
        }
    }
}

/** What is wrong with a site as it is written, field by field, with the same paths as the server's problems. */
export function validateSite(draft: SiteDraft, ctx: ValidationContext, t: Translate): FieldProblem[] {
    const out: FieldProblem[] = [];
    const identifier = draft.identifier.trim();
    if (!IDENTIFIER.test(identifier)) {
        out.push({ path: 'identifier', message: t('validation.identifier') });
    } else if (ctx.isNew && ctx.others.some((site) => site.identifier === identifier)) {
        out.push({ path: 'identifier', message: t('validation.identifier.taken', { identifier }) });
    }
    if (draft.name.trim() === '' || draft.name.trim().length > 100) {
        out.push({ path: 'name', message: t('validation.name') });
    }
    if (draft.rootLocationId === null || !(draft.rootLocationId > 0)) {
        out.push({ path: 'rootLocationId', message: t('validation.root.required') });
    } else if (ctx.others.some((site) => site.rootLocationId === draft.rootLocationId)) {
        out.push({ path: 'rootLocationId', message: t('validation.root.used') });
    }
    if (draft.design.trim() === '' || (ctx.options.designs.length > 0 && !ctx.options.designs.includes(draft.design))) {
        out.push({ path: 'design', message: t('validation.design') });
    }
    if (draft.base !== '' && ctx.options.baseGroups.length > 0 && !ctx.options.baseGroups.includes(draft.base)) {
        out.push({ path: 'base', message: t('validation.base', { base: draft.base }) });
    }
    if (draft.varDir.trim() !== '' && !VAR_DIR.test(draft.varDir.trim())) {
        out.push({ path: 'varDir', message: t('validation.var_dir') });
    }
    if (draft.layoutRemoteId.trim() !== '' && !REMOTE_ID.test(draft.layoutRemoteId.trim())) {
        out.push({ path: 'layoutRemoteId', message: t('validation.layout') });
    }
    if (draft.email.trim() !== '' && (draft.email.trim().length > 255 || !EMAIL.test(draft.email.trim()))) {
        out.push({ path: 'email', message: t('validation.email') });
    }
    if (draft.address.trim().length > 255) {
        out.push({ path: 'address', message: t('validation.address') });
    }

    if (draft.siteaccesses.length === 0) {
        out.push({ path: 'siteaccesses', message: t('validation.siteaccesses.none') });
    }
    const takenBy = taken(ctx.others);
    draft.siteaccesses.forEach((row, index) => checkRow(`siteaccesses[${index}]`, row, draft, ctx, takenBy, t, out));
    draft.adminSiteaccesses.forEach((row, index) => checkRow(`adminSiteaccesses[${index}]`, row, draft, ctx, takenBy, t, out));

    const cookie = draft.sessionCookieDomain.trim();
    if (cookie !== '') {
        const bare = cookie.replace(/^\./, '');
        if (cookie !== cookie.toLowerCase() || !COOKIE_DOMAIN.test(bare)) {
            out.push({ path: 'sessionCookieDomain', message: t('validation.cookie.invalid') });
        } else {
            const hosts = [...draft.siteaccesses, ...draft.adminSiteaccesses].map((row) => normalizedHost(row.host)).filter((h) => h !== '');
            const uncovered = hosts.find((host) => host !== bare && !host.endsWith(`.${bare}`));
            if (uncovered !== undefined) {
                out.push({ path: 'sessionCookieDomain', message: t('validation.cookie.cover', { host: uncovered }) });
            }
        }
    }

    return out;
}

/** The problems of the page and of the server together, without the same sentence twice for a field. */
export function mergeProblems(...lists: ReadonlyArray<readonly FieldProblem[]>): FieldProblem[] {
    const seen = new Set<string>();

    return lists.flat().filter((p) => {
        const key = `${p.path}\u0000${p.message}`;
        if (seen.has(key)) {
            return false;
        }
        seen.add(key);

        return true;
    });
}

export function hasProblems(problems: readonly FieldProblem[]): boolean {
    return problems.length > 0;
}

export { messageFor };
