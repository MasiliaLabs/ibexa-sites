import { request } from '@masilia-admin-kit/http';
import type { ApiResult, Fetcher } from '@masilia-admin-kit/http';
import { toPayload } from './payload';
import type { SiteDraft, SiteDto, SitesData } from './types';

/** A location told by name: what the picker shows. */
export interface LocationDto {
    id: number;
    name: string;
    path: string[];
    pathString: string;
    contentId: number;
    remoteId: string;
    contentType: string;
}

export interface ProblemsDto {
    problems: Array<{ path: string; message: string }>;
}

/** The base of the API of the page, from the address of its `data` route (the page knows its siteaccess prefix: the script does not guess it). */
export function apiBase(dataUrl: string): string {
    return dataUrl.replace(/\/data$/, '');
}

/** The calls of the sites page: one function for each thing it asks of the server. */
export function createApi(fetcher: Fetcher, failed: string, base: string) {
    return {
        data: () => request<SitesData>(fetcher, 'GET', `${base}/data`, undefined, failed),
        location: (id: number) => request<LocationDto>(fetcher, 'GET', `${base}/locations/${id}`, undefined, failed),
        layout: (remoteId: string) => request<LocationDto>(fetcher, 'GET', `${base}/layouts/${encodeURIComponent(remoteId)}`, undefined, failed),
        validate: (draft: SiteDraft, isNew: boolean, asActive: boolean) => request<ProblemsDto>(fetcher, 'POST', `${base}/validate`, { isNew, asActive, site: toPayload(draft) }, failed),
        create: (draft: SiteDraft) => request<{ site: SiteDto }>(fetcher, 'POST', base, toPayload(draft), failed),
        update: (identifier: string, draft: SiteDraft) => request<{ site: SiteDto }>(fetcher, 'PUT', `${base}/${identifier}`, toPayload(draft), failed),
        setStatus: (identifier: string, status: 'active' | 'disabled') => request<{ site: SiteDto }>(fetcher, 'PUT', `${base}/${identifier}/status`, { status }, failed),
        remove: (identifier: string) => request<{ identifier: string }>(fetcher, 'DELETE', `${base}/${identifier}`, undefined, failed),
        setDefault: (siteaccess: string) => request<{ defaultSiteaccess: string | null }>(fetcher, 'PUT', `${base}/settings/default`, { siteaccess }, failed),
    };
}

export type Api = ReturnType<typeof createApi>;
export type { ApiResult };
