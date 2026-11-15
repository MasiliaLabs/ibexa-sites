import React, { useMemo, useState } from 'react';

import { iconPath } from '../shared/icons';
import { AssetsProvider } from '../shared/ids';
import { Spinner } from '../shared/Spinner';
import { Tabs } from '../shared/Tabs';
import { createApi } from './api';
import { DefaultCard } from './DefaultCard';
import { t } from './messages';
import { SiteEditor } from './SiteEditor';
import { SitesList } from './SitesList';
import type { SiteDto } from './types';
import { useSites } from './useSites';

interface SitesAppProps {
    /** The configuration of the Universal Discovery Widget for the root and for the layout of a site */
    udw: { root: Record<string, unknown>; layout: Record<string, unknown> };
}

type Editing = { site: SiteDto | null } | null;

/** The sites page: the list with its filters, the default siteaccess, and the panel that edits a site. */
export function SitesApp({ udw }: SitesAppProps): React.ReactElement {
    const api = useMemo(() => createApi((input, init) => window.fetch(input, init), t('ui.failed')), []);
    const { data, loading, failed, reload } = useSites(api);
    const [editing, setEditing] = useState<Editing>(null);
    const [tab, setTab] = useState('sites');

    let body: React.ReactElement;
    if (loading) {
        body = <p className="masilia-sites-muted" role="status"><Spinner label={t('ui.loading')} /> {t('ui.loading')}</p>;
    } else if (failed || data === null) {
        body = <p className="ibexa-alert ibexa-alert--error" role="alert">{t('ui.load_failed')}</p>;
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
        <AssetsProvider value={{ getIconPath: iconPath }}>
            <div className="masilia-sites-react">
                <p className="masilia-sites-muted">{t('ui.intro')}</p>
                {body}
                <div className="ibexa-alert ibexa-alert--info masilia-sites-limits" role="note">{t('ui.limits')}</div>
            </div>
        </AssetsProvider>
    );
}
