import React, { useMemo, useState } from 'react';

import { ConfirmDialog } from '../shared/ConfirmDialog';
import { FilterCard, FilterSelect, FilterZone } from '../shared/FilterCard';
import { ExternalLink } from '../shared/ExternalLink';
import { IconButton } from '../shared/IconButton';
import { Button } from '../shared/ids';
import { notify } from '../shared/notify';
import { SearchInput } from '../shared/SearchInput';
import { DataTable, TableHeader } from '../shared/Table';
import type { Column } from '../shared/Table';
import { StatusChip } from '../shared/StatusChip';
import type { ChipTone } from '../shared/StatusChip';
import { addressOf } from './addresses';
import type { Api } from './api';
import { t } from './messages';
import type { SiteDto, SiteStatus, SitesData } from './types';

const TONES: Record<SiteStatus, ChipTone> = { draft: 'warning', active: 'success', disabled: 'neutral' };

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

    const columns: Column<SiteDto>[] = [
        {
            key: 'site',
            header: t('column.site'),
            render: (site) => (
                <>
                    <strong>{site.name}</strong>
                    <br />
                    <span className="masilia-sites-muted">{site.design}{site.identifier !== site.name ? ` · ${site.identifier}` : ''}</span>
                </>
            ),
        },
        { key: 'status', header: t('column.status'), render: (site) => <StatusChip tone={TONES[site.status]}>{t(`status.${site.status}`)}</StatusChip> },
        {
            key: 'siteaccesses',
            header: t('column.addresses'),
            render: (site) => (
                <>
                    {site.siteaccesses.map((sa) => {
                        const url = addressOf({ host: sa.host, uriPrefix: sa.uriPrefix ?? '' });
                        const shownUrl = url.replace(/^https?:\/\//, '');

                        return (
                            <div className="masilia-sites-table__sa" key={sa.name}>
                                {site.status === 'active' ? <ExternalLink href={url} label={t('addresses.open')}>{shownUrl}</ExternalLink> : <span className="masilia-sites-muted">{shownUrl}</span>}
                                {' '}<span className="masilia-sites-muted">{sa.languages[0]}</span>
                            </div>
                        );
                    })}
                    {site.adminSiteaccesses.length > 0 ? <div className="masilia-sites-muted">{t('list.admins', { count: site.adminSiteaccesses.length })}</div> : null}
                </>
            ),
        },
        ...(data.canManage ? [{
            key: 'actions',
            header: '',
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
            <FilterCard>
                <FilterZone><SearchInput value={query} onChange={setQuery} placeholder={t('list.search')} clearLabel={t('list.search.clear')} /></FilterZone>
                <FilterZone>
                    <FilterSelect
                        label={t('column.status')}
                        value={status}
                        onChange={(value) => setStatus(value as '' | SiteStatus)}
                        options={[{ value: '', label: t('list.filter.all') }, ...(['draft', 'active', 'disabled'] as const).map((s) => ({ value: s, label: t(`status.${s}`) }))]}
                    />
                </FilterZone>
            </FilterCard>

            <TableHeader
                headline={t('list.title')}
                count={shown.length}
                actions={data.canManage ? <Button type="primary" icon="create" onClick={onNew}>{t('list.new')}</Button> : undefined}
            />
            <DataTable
                columns={columns}
                rows={shown}
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
