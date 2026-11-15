import { useCallback, useEffect, useState } from 'react';

import type { Api, LocationDto } from './api';
import type { SitesData } from './types';

export interface SitesState {
    data: SitesData | null;
    loading: boolean;
    failed: boolean;
    reload: () => Promise<void>;
}

/** The sites, the options and the default siteaccess, loaded once and again after each change. */
export function useSites(api: Api): SitesState {
    const [data, setData] = useState<SitesData | null>(null);
    const [loading, setLoading] = useState(true);
    const [failed, setFailed] = useState(false);

    const reload = useCallback(async (): Promise<void> => {
        const result = await api.data();
        if (result.ok) {
            setData(result.data);
            setFailed(false);
        } else {
            setFailed(true);
        }
        setLoading(false);
    }, [api]);

    useEffect(() => {
        void reload();
    }, [reload]);

    return { data, loading, failed, reload };
}

/** A location or a layout told by name, looked up when its id (or remote id) is known and no name is at hand. */
export function useLocationView(api: Api, key: { locationId?: number | null; layoutRemoteId?: string }): LocationDto | null {
    const [view, setView] = useState<LocationDto | null>(null);
    const { locationId, layoutRemoteId } = key;

    useEffect(() => {
        let cancelled = false;
        setView(null);
        const lookup = locationId !== undefined && locationId !== null && locationId > 0
            ? api.location(locationId)
            : layoutRemoteId !== undefined && layoutRemoteId !== '' ? api.layout(layoutRemoteId) : null;
        void lookup?.then((result) => {
            if (!cancelled && result.ok) {
                setView(result.data);
            }
        });

        return () => {
            cancelled = true;
        };
    }, [api, locationId, layoutRemoteId]);

    return view;
}
