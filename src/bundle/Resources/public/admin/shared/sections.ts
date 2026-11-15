/** The sections of the site editor, and which one a field belongs to: an error under a field opens its section. */
export type SectionId = 'identity' | 'addresses' | 'settings' | 'admins' | 'advanced';

export const SECTIONS: readonly SectionId[] = ['identity', 'addresses', 'settings', 'admins', 'advanced'];

/** The section of a field, by the path the validation gives it ("siteaccesses[1].host" is in "addresses"). */
export function sectionOf(path: string): SectionId {
    const field = path.split(/[.[]/)[0];
    switch (field) {
        case 'identifier':
        case 'name':
            return 'identity';
        case 'siteaccesses':
            return 'addresses';
        case 'adminSiteaccesses':
            return 'admins';
        case 'varDir':
        case 'sessionCookieDomain':
        case 'adminSiteaccess':
            return 'advanced';
        default:
            return 'settings';
    }
}

/** The sections that hold at least one of these problems. */
export function sectionsWithProblems(paths: readonly string[]): Set<SectionId> {
    return new Set(paths.map(sectionOf));
}

/** The values of the optional parts of a site: a section that holds something is open to show it. */
export interface Filled {
    admins: boolean;
    advanced: boolean;
}

/** Whether a section is open: the main ones always, the optional ones when they hold something, any one that has a problem to show. */
export function isOpen(section: SectionId, filled: Filled, withProblems: ReadonlySet<SectionId>): boolean {
    if (withProblems.has(section)) {
        return true;
    }

    return section === 'admins' ? filled.admins : section === 'advanced' ? filled.advanced : true;
}
