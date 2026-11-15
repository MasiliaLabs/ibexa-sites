import { request } from '../shared/http';
import type { ApiResult, Fetcher } from '../shared/http';
import { toPayload } from './payload';
import type { SiteDraft, SiteDto, SitesData } from './types';

const BASE = '/api/masilia/sites';

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

/** The calls of the sites page: one function for each thing it asks of the server. */
export function createApi(fetcher: Fetcher, failed: string) {
    return {
        data: () => request<SitesData>(fetcher, 'GET', `${BASE}/data`, undefined, failed),
        location: (id: number) => request<LocationDto>(fetcher, 'GET', `${BASE}/locations/${id}`, undefined, failed),
        layout: (remoteId: string) => request<LocationDto>(fetcher, 'GET', `${BASE}/layouts/${encodeURIComponent(remoteId)}`, undefined, failed),
        validate: (draft: SiteDraft, isNew: boolean, asActive: boolean) => request<ProblemsDto>(fetcher, 'POST', `${BASE}/validate`, { isNew, asActive, site: toPayload(draft) }, failed),
        create: (draft: SiteDraft) => request<{ site: SiteDto }>(fetcher, 'POST', BASE, toPayload(draft), failed),
        update: (identifier: string, draft: SiteDraft) => request<{ site: SiteDto }>(fetcher, 'PUT', `${BASE}/${identifier}`, toPayload(draft), failed),
        setStatus: (identifier: string, status: 'active' | 'disabled') => request<{ site: SiteDto }>(fetcher, 'PUT', `${BASE}/${identifier}/status`, { status }, failed),
        remove: (identifier: string) => request<{ identifier: string }>(fetcher, 'DELETE', `${BASE}/${identifier}`, undefined, failed),
        setDefault: (siteaccess: string) => request<{ defaultSiteaccess: string | null }>(fetcher, 'PUT', `${BASE}/settings/default`, { siteaccess }, failed),
    };
}

export type Api = ReturnType<typeof createApi>;
export type { ApiResult };
