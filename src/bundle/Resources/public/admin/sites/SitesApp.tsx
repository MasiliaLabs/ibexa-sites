import React, { useMemo, useState } from 'react';

import { AdminProviders } from '@masilia-admin-kit/AdminProviders';
import { Alert } from '@masilia-admin-kit/Alert';
import { Muted } from '@masilia-admin-kit/Layout';
import { Notice } from '@masilia-admin-kit/Notice';
import { Spinner } from '@masilia-admin-kit/Spinner';
import { Tabs } from '@masilia-admin-kit/Tabs';
import { apiBase, createApi } from './api';
import { DefaultCard } from './DefaultCard';
import { t } from './messages';
import { SiteEditor } from './SiteEditor';
import { SitesList } from './SitesList';
import type { SiteDto } from './types';
import { useSites } from './useSites';

interface SitesAppProps {
    /** The configuration of the Universal Discovery Widget for the root and for the layout of a site */
    udw: { root: Record<string, unknown>; layout: Record<string, unknown> };
    /** The address of the `data` route of the API, as the page's siteaccess builds it */
    dataUrl: string;
}

type Editing = { site: SiteDto | null } | null;

/** The sites page: the list with its filters, the default siteaccess, and the panel that edits a site. */
export function SitesApp({ udw, dataUrl }: SitesAppProps): React.ReactElement {
    const api = useMemo(() => createApi((input, init) => window.fetch(input, init), t('ui.failed'), apiBase(dataUrl)), [dataUrl]);
    const { data, loading, failed, reload } = useSites(api);
    const [editing, setEditing] = useState<Editing>(null);
    const [tab, setTab] = useState('sites');

    let body: React.ReactElement;
    if (loading) {
        body = <p role="status"><Muted><Spinner label={t('ui.loading')} /> {t('ui.loading')}</Muted></p>;
    } else if (failed || data === null) {
        body = <Alert type="error" title={t('ui.load_failed')} />;
    } else {
        body = (
            <>
                <Tabs
                    label={t('ui.title')}
                    active={tab}
                    onChange={setTab}
                    tabs={[
                        { id: 'sites', label: t('tab.sites'), content: <SitesList api={api} data={data} onEdit={(site) => setEditing({ site })} onNew={() => setEditing({ site: null })} onChanged={() => void reload()} /> },
                        { id: 'default', label: t('tab.default'), content: <DefaultCard api={api} defaultSiteaccess={data.defaultSiteaccess} choices={data.defaultChoices} canManage={data.canManage} onSaved={() => void reload()} /> },
                    ]}
                />
                {editing !== null ? (
                    <SiteEditor
                        api={api}
                        data={data}
                        site={editing.site}
                        udw={udw}
                        onClose={() => setEditing(null)}
                        onSaved={() => { setEditing(null); void reload(); }}
                    />
                ) : null}
            </>
        );
    }

    return (
        <AdminProviders t={t} className="masilia-sites-react">
            <Notice>{t('ui.intro')}</Notice>
            {body}
            <Notice>{t('ui.limits')}</Notice>
        </AdminProviders>
    );
}
