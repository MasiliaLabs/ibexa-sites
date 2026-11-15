/** A site as the server gives it ({@link ../../../../../contracts/Site.php} `toArray()`). */
export type SiteStatus = 'draft' | 'active' | 'disabled';

export interface SiteaccessDto {
    name: string;
    languages: string[];
    host: string;
    uriPrefix: string | null;
}

export interface SiteDto {
    identifier: string;
    name: string;
    status: SiteStatus;
    rootLocationId: number;
    design: string;
    base: string | null;
    sessionCookieDomain: string | null;
    adminSiteaccess: string | null;
    varDir: string | null;
    layoutRemoteId: string | null;
    email: string | null;
    address: string | null;
    siteaccesses: SiteaccessDto[];
    adminSiteaccesses: SiteaccessDto[];
}

/** What can be chosen to make a site. */
export interface Options {
    languages: string[];
    designs: string[];
    baseGroups: string[];
    adminSiteaccesses: string[];
}

/** What the page loads: the sites, the options, the default siteaccess and the right to change them. */
export interface SitesData {
    canManage: boolean;
    sites: SiteDto[];
    options: Options;
    defaultSiteaccess: string | null;
    defaultChoices: string[];
}

/** A siteaccess being written: its fields are text, and it has a key that does not change when a row above it goes. */
export interface SiteaccessDraft {
    key: string;
    name: string;
    /** The languages in order: the first is the language of the siteaccess */
    languages: string[];
    host: string;
    uriPrefix: string;
}

/** A site being written, as the form holds it. */
export interface SiteDraft {
    identifier: string;
    name: string;
    rootLocationId: number | null;
    design: string;
    base: string;
    sessionCookieDomain: string;
    adminSiteaccess: string;
    varDir: string;
    layoutRemoteId: string;
    email: string;
    address: string;
    siteaccesses: SiteaccessDraft[];
    adminSiteaccesses: SiteaccessDraft[];
}
