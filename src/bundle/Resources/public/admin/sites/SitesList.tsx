import React, { useMemo, useState } from 'react';

import { ConfirmDialog } from '@masilia-admin-kit/ConfirmDialog';
import { FilterCard, FilterSelect } from '@masilia-admin-kit/FilterCard';
import { ExternalLink } from '@masilia-admin-kit/ExternalLink';
import { IconButton } from '@masilia-admin-kit/IconButton';
import { Button } from '@masilia-admin-kit/ids';
import { notify } from '@masilia-admin-kit/notify';
import { SearchInput } from '@masilia-admin-kit/SearchInput';
import { DataTable, TableHeader } from '@masilia-admin-kit/Table';
import type { Column } from '@masilia-admin-kit/Table';
import { StatusTag } from '@masilia-admin-kit/StatusTag';
import type { Api } from './api';
import { t } from './messages';
import type { SiteDto, SiteStatus, SitesData } from './types';

type Pending = { kind: 'activate' | 'disable' | 'delete'; site: SiteDto } | null;

interface SitesListProps {
    api: Api;
    data: SitesData;
    onEdit: (site: SiteDto) => void;
    onNew: () => void;
    onChanged: () => void;
}

/** Whether a site matches what was typed in the search box: its name, identifier, siteaccess names or hosts. */
export function matches(site: SiteDto, query: string): boolean {
    const needle = query.trim().toLowerCase();
    if (needle === '') {
        return true;
    }
    const haystack = [site.name, site.identifier, ...[...site.siteaccesses, ...site.adminSiteaccesses].flatMap((sa) => [sa.name, sa.host])];

    return haystack.some((text) => text.toLowerCase().includes(needle));
}

export function SitesList({ api, data, onEdit, onNew, onChanged }: SitesListProps): React.ReactElement {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState<'' | SiteStatus>('');
    const [pending, setPending] = useState<Pending>(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | undefined>(undefined);

    const shown = useMemo(() => data.sites.filter((site) => (status === '' || site.status === status) && matches(site, query)), [data.sites, status, query]);
    const holdsDefault = (site: SiteDto): boolean => data.defaultSiteaccess !== null && site.siteaccesses.some((sa) => sa.name === data.defaultSiteaccess);

    const run = async (): Promise<void> => {
        if (pending === null) {
            return;
        }
        setBusy(true);
        const { kind, site } = pending;
        const result = kind === 'delete' ? await api.remove(site.identifier) : await api.setStatus(site.identifier, kind === 'activate' ? 'active' : 'disabled');
        setBusy(false);
        if (result.ok) {
            setPending(null);
            setError(undefined);
            notify('success', t(kind === 'delete' ? 'flash.deleted' : kind === 'activate' ? 'flash.activated' : 'flash.disabled', { name: site.name }));
            onChanged();
        } else {
            setError(result.message);
        }
    };

    /** The hosts of some siteaccesses, once each, as links when the site is served. */
    const hostLinks = (site: SiteDto, siteaccesses: SiteDto['siteaccesses']): React.ReactNode => {
        const hosts = [...new Set(siteaccesses.map((sa) => sa.host))];
        if (hosts.length === 0) {
            return <span className="masilia-sites-muted">—</span>;
        }

        return hosts.map((host) => (
            <div className="masilia-sites-table__sa" key={host}>
                {site.status === 'active' ? <ExternalLink href={`https://${host}/`} label={t('addresses.open')}>{host}</ExternalLink> : <span className="masilia-sites-muted">{host}</span>}
            </div>
        ));
    };

    const columns: Column<SiteDto>[] = [
        {
            key: 'site',
            header: t('column.site'),
            render: (site) => (
                <>
                    <strong>{site.name}</strong>
                    <br />
                    <span className="masilia-sites-muted">{site.identifier}</span>
                </>
            ),
        },
        { key: 'design', header: t('column.design'), render: (site) => site.design },
        { key: 'status', header: t('column.status'), render: (site) => <StatusTag status={site.status}>{t(`status.${site.status}`)}</StatusTag> },
        { key: 'domain', header: t('column.domain'), render: (site) => hostLinks(site, site.siteaccesses) },
        { key: 'admin', header: t('column.admin'), render: (site) => hostLinks(site, site.adminSiteaccesses) },
        ...(data.canManage ? [{
            key: 'actions',
            header: <span className="masilia-kit-sr-only">{t('column.actions')}</span>,
            actions: true,
            render: (site: SiteDto) => (
                <>
                    <IconButton icon="edit" label={`${t('action.edit')}: ${site.name}`} onClick={() => onEdit(site)} />
                    {site.status === 'active'
                        ? <Button type="secondary" size="small" disabled={holdsDefault(site)} title={holdsDefault(site) ? t('default.held') : undefined} onClick={() => { setError(undefined); setPending({ kind: 'disable', site }); }}>{t('action.disable')}</Button>
                        : <Button type="secondary" size="small" onClick={() => { setError(undefined); setPending({ kind: 'activate', site }); }}>{t('action.activate')}</Button>}
                    <IconButton icon="trash" label={holdsDefault(site) ? t('default.held') : `${t('action.delete')}: ${site.name}`} disabled={holdsDefault(site)} onClick={() => { setError(undefined); setPending({ kind: 'delete', site }); }} />
                </>
            ),
        }] : []),
    ];

    return (
        <section className="masilia-sites-list">
            <FilterCard search={<SearchInput value={query} onChange={setQuery} placeholder={t('list.search')} clearLabel={t('list.search.clear')} />}>
                <FilterSelect
                    label={t('column.status')}
                    value={status}
                    onChange={(value) => setStatus(value as '' | SiteStatus)}
                    emptyLabel={t('list.filter.all')}
                    options={(['draft', 'active', 'disabled'] as const).map((s) => ({ value: s, label: t(`status.${s}`) }))}
                />
            </FilterCard>

            <TableHeader
                headline={t('list.title')}
                count={shown.length}
                actions={data.canManage ? <Button type="primary" icon="create" onClick={onNew}>{t('list.new')}</Button> : undefined}
            />
            <DataTable
                columns={columns}
                rows={shown}
                label={t('list.title')}
                rowKey={(site) => site.identifier}
                empty={data.sites.length === 0
                    ? { info: t('list.empty'), action: data.canManage ? <Button type="secondary" size="small" onClick={onNew}>{t('list.new')}</Button> : undefined }
                    : { info: t('list.none_found') }}
            />

            {pending !== null ? (
                <ConfirmDialog
                    title={t(`confirm.${pending.kind}.title`, { name: pending.site.name })}
                    confirmLabel={t(pending.kind === 'activate' ? 'action.activate' : pending.kind === 'disable' ? 'action.disable' : 'action.delete')}
                    cancelLabel={t('action.cancel')}
                    danger={pending.kind === 'delete'}
                    busy={busy}
                    onConfirm={() => void run()}
                    onCancel={() => setPending(null)}
                >
                    <p>{t(`confirm.${pending.kind}.text`)}</p>
                    {error !== undefined ? <p className="ibexa-form-error__row" role="alert">{error}</p> : null}
                </ConfirmDialog>
            ) : null}
        </section>
    );
}
