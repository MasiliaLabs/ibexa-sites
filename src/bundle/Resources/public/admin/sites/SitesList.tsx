import React, { useMemo, useState } from 'react';

import { ConfirmDialog } from '../shared/ConfirmDialog';
import { Button } from '../shared/ids';
import { notify } from '../shared/notify';
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

    return (
        <section className="masilia-sites-list">
            <div className="masilia-sites-toolbar">
                <input
                    className="ibexa-input ibexa-input--text form-control masilia-sites-toolbar__search"
                    type="search"
                    value={query}
                    placeholder={t('list.search')}
                    aria-label={t('list.search')}
                    onChange={(event) => setQuery(event.target.value)}
                />
                <select className="ibexa-input ibexa-input--text form-select masilia-kit-select masilia-sites-toolbar__status" value={status} aria-label={t('column.status')} onChange={(event) => setStatus(event.target.value as '' | SiteStatus)}>
                    <option value="">{t('list.filter.all')}</option>
                    {(['draft', 'active', 'disabled'] as const).map((s) => <option key={s} value={s}>{t(`status.${s}`)}</option>)}
                </select>
                {data.canManage ? <Button type="primary" icon="create" onClick={onNew}>{t('list.new')}</Button> : null}
            </div>

            {data.sites.length === 0 ? <p className="masilia-sites-muted">{t('list.empty')}</p> : shown.length === 0 ? <p className="masilia-sites-muted">{t('list.none_found')}</p> : (
                <table className="table ibexa-table masilia-sites-table">
                    <thead>
                        <tr>
                            <th>{t('column.site')}</th>
                            <th>{t('column.status')}</th>
                            <th>{t('column.siteaccesses')}</th>
                            <th>{t('column.design')}</th>
                            {data.canManage ? <th>{t('column.actions')}</th> : null}
                        </tr>
                    </thead>
                    <tbody>
                        {shown.map((site) => (
                            <tr key={site.identifier}>
                                <td><strong>{site.name}</strong><br /><code>{site.identifier}</code></td>
                                <td><StatusChip tone={TONES[site.status]}>{t(`status.${site.status}`)}</StatusChip></td>
                                <td>
                                    {site.siteaccesses.map((sa) => {
                                        const url = addressOf({ host: sa.host, uriPrefix: sa.uriPrefix ?? '' });

                                        return (
                                            <div className="masilia-sites-table__sa" key={sa.name}>
                                                <code>{sa.name}</code> <span className="masilia-sites-muted">{sa.languages.join(' → ')}</span>{' '}
                                                {site.status === 'active' ? <a href={url} target="_blank" rel="noreferrer" title={t('addresses.open')}>{url.replace(/^https?:\/\//, '')}</a> : <span className="masilia-sites-muted">{url.replace(/^https?:\/\//, '')}</span>}
                                            </div>
                                        );
                                    })}
                                    {site.adminSiteaccesses.map((sa) => (
                                        <div className="masilia-sites-table__sa" key={sa.name}>
                                            <code>{sa.name}</code> <span className="masilia-sites-muted">admin · {sa.host}</span>
                                        </div>
                                    ))}
                                </td>
                                <td>{site.design}{site.base !== null ? <><br /><span className="masilia-sites-muted">{site.base}</span></> : null}</td>
                                {data.canManage ? (
                                    <td className="masilia-sites-table__actions">
                                        <Button type="tertiary" size="small" icon="edit" ariaLabel={`${t('action.edit')}: ${site.name}`} title={t('action.edit')} onClick={() => onEdit(site)} />
                                        {site.status === 'active'
                                            ? <Button type="secondary" size="small" disabled={holdsDefault(site)} title={holdsDefault(site) ? t('default.held') : undefined} onClick={() => { setError(undefined); setPending({ kind: 'disable', site }); }}>{t('action.disable')}</Button>
                                            : <Button type="secondary" size="small" onClick={() => { setError(undefined); setPending({ kind: 'activate', site }); }}>{t('action.activate')}</Button>}
                                        <Button type="tertiary" size="small" icon="trash" ariaLabel={`${t('action.delete')}: ${site.name}`} title={holdsDefault(site) ? t('default.held') : t('action.delete')} disabled={holdsDefault(site)} onClick={() => { setError(undefined); setPending({ kind: 'delete', site }); }} />
                                    </td>
                                ) : null}
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}

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
