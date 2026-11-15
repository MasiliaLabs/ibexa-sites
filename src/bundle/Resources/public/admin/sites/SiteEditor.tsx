import React, { useEffect, useMemo, useRef, useState } from 'react';

import { ConfirmDialog } from '../shared/ConfirmDialog';
import { Dialog } from '../shared/Dialog';
import { DropdownField } from '../shared/Dropdown';
import { TextField } from '../shared/Field';
import { Section } from '../shared/Section';
import { isOpen, sectionsWithProblems } from '../shared/sections';
import { messageFor } from '../shared/http';
import type { FieldProblem } from '../shared/http';
import { Button } from '../shared/ids';
import { notify } from '../shared/notify';
import { OrderedChoice } from '../shared/OrderedChoice';
import { Spinner } from '../shared/Spinner';
import { UdwPicker } from '../shared/UdwPicker';
import type { PickedView } from '../shared/UdwPicker';
import { useDebounced } from '../shared/useDebounced';
import { addressLines } from './addresses';
import type { Api, LocationDto } from './api';
import { t } from './messages';
import { draftFrom, emptyDraft, isChanged, toPayload } from './payload';
import { SiteaccessRows } from './SiteaccessRows';
import { commonCookieDomain, initialLanguages, suggestIdentifier, suggestSiteaccesses } from './suggestions';
import type { SiteDraft, SiteDto, SitesData } from './types';
import { useLocationView } from './useSites';
import { hasProblems, mergeProblems, validateSite } from './validation';

interface SiteEditorProps {
    api: Api;
    data: SitesData;
    /** The site being changed, or null for a new one */
    site: SiteDto | null;
    /** The configuration of the Universal Discovery Widget for the root and for the layout (from Twig) */
    udw: { root: Record<string, unknown>; layout: Record<string, unknown> };
    onClose: () => void;
    onSaved: (site: SiteDto, created: boolean) => void;
}

function topLevel(path: string): string {
    return path.split(/[.[]/)[0];
}

function view(id: number | null, dto: LocationDto | null, fallbackName: string): PickedView | null {
    if (id === null || id <= 0) {
        return null;
    }

    return { id, name: dto?.name ?? fallbackName, path: dto?.path ?? [] };
}

/** The form of a site, in a panel: sections, live checks (here, then the server), a picker for the root and the layout. */
export function SiteEditor({ api, data, site, udw, onClose, onSaved }: SiteEditorProps): React.ReactElement {
    const isNew = site === null;
    const [draft, setDraft] = useState<SiteDraft>(() => (site === null ? { ...emptyDraft(), design: data.options.designs.length === 1 ? data.options.designs[0] : '' } : draftFrom(site)));
    const [identifierTouched, setIdentifierTouched] = useState(!isNew);
    const [touched, setTouched] = useState<ReadonlySet<string>>(new Set());
    const [submitted, setSubmitted] = useState(false);
    const [serverProblems, setServerProblems] = useState<FieldProblem[]>([]);
    const [checking, setChecking] = useState(false);
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState<string | undefined>(undefined);
    const [discarding, setDiscarding] = useState(false);
    const [suggestLanguages, setSuggestLanguages] = useState<string[]>(() => initialLanguages(data.options.languages));
    const [suggestHost, setSuggestHost] = useState('');

    const others = useMemo(() => data.sites.filter((s) => s.identifier !== site?.identifier), [data.sites, site]);
    const clientProblems = useMemo(() => validateSite(draft, { isNew, others, options: data.options }, t), [draft, isNew, others, data.options]);
    const problems = useMemo(() => mergeProblems(clientProblems, serverProblems), [clientProblems, serverProblems]);
    const changed = isChanged(draft, site);

    const touch = (...fields: string[]): void => setTouched((current) => new Set([...current, ...fields]));
    const shown = (path: string): string | undefined => (submitted || touched.has(topLevel(path)) ? messageFor(problems, path) : undefined);
    const set = (changes: Partial<SiteDraft>, field?: string): void => {
        setDraft((current) => ({ ...current, ...changes }));
        if (field !== undefined) {
            touch(field);
        }
    };

    // The server checks what only it knows (Ibexa's locations, the YAML, the database), a moment after the person stops typing.
    const payload = JSON.stringify(toPayload(draft));
    const settled = useDebounced(payload, 600);
    const latest = useRef(draft);
    latest.current = draft;
    useEffect(() => {
        if (draft.identifier.trim() === '' && draft.name.trim() === '') {
            setServerProblems([]);
            setChecking(false);

            return undefined;
        }
        let cancelled = false;
        setChecking(true);
        void api.validate(latest.current, isNew, false).then((result) => {
            if (cancelled) {
                return;
            }
            setChecking(false);
            setServerProblems(result.ok ? result.data.problems : []);
        });

        return () => {
            cancelled = true;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps -- it runs when what was written has settled
    }, [settled, api, isNew]);

    const rootView = useLocationView(api, { locationId: draft.rootLocationId });
    const layoutView = useLocationView(api, { layoutRemoteId: draft.layoutRemoteId });

    const hosts = [...draft.siteaccesses, ...draft.adminSiteaccesses].map((row) => row.host);
    const cookieSuggestion = commonCookieDomain(hosts);
    const lines = addressLines(draft.siteaccesses);

    const close = (): void => (changed && !saving ? setDiscarding(true) : onClose());
    const save = async (): Promise<void> => {
        setSubmitted(true);
        if (hasProblems(clientProblems)) {
            return;
        }
        setSaving(true);
        setSaveError(undefined);
        const result = site === null ? await api.create(draft) : await api.update(site.identifier, draft);
        setSaving(false);
        if (result.ok) {
            notify('success', t(site === null ? 'flash.created' : 'flash.updated', { name: result.data.site.name }));
            onSaved(result.data.site, site === null);
        } else {
            // What the server tied to a field is shown under that field; the sentence on top is for what it did not.
            setSaveError(result.problems.length > 0 && result.problems.every((p) => p.path !== '') ? undefined : result.message);
            setServerProblems(result.problems);
        }
    };

    // The optional sections open with what they hold, and any section opens on a problem that is shown under one of its fields.
    const filled = { admins: draft.adminSiteaccesses.length > 0, advanced: draft.varDir !== '' || draft.sessionCookieDomain !== '' || draft.adminSiteaccess !== '' };
    const withProblems = sectionsWithProblems(problems.filter((p) => submitted || touched.has(topLevel(p.path))).map((p) => p.path));
    const unpathed = serverProblems.filter((p) => p.path === '');
    const title = site === null ? t('editor.new') : t('editor.edit', { name: site.name });

    return (
        <>
            <Dialog
                title={title}
                variant="panel"
                onClose={close}
                closeLabel={t('action.close')}
                footer={(
                    <>
                        <span className="masilia-sites-editor__status" role="status" aria-live="polite">
                            {saving ? <><Spinner label={t('editor.saving')} /> {t('editor.saving')}</> : checking ? <><Spinner label={t('editor.checking')} /> {t('editor.checking')}</> : null}
                        </span>
                        <Button type="tertiary" onClick={close}>{t('action.cancel')}</Button>
                        <Button type="primary" disabled={saving || (!isNew && !changed)} onClick={() => void save()}>{isNew ? t('action.create') : t('action.save')}</Button>
                    </>
                )}
            >
                {saveError !== undefined || unpathed.length > 0 ? (
                    <div className="ibexa-alert ibexa-alert--error" role="alert">
                        <strong>{t('editor.server_problems')}</strong>
                        <ul>{(unpathed.length > 0 ? unpathed.map((p) => p.message) : [saveError ?? '']).map((m) => <li key={m}>{m}</li>)}</ul>
                    </div>
                ) : null}

                <Section title={t('section.identity')} open={isOpen('identity', filled, withProblems)}>
                    <TextField
                        label={t('field.name')}
                        hint={t('field.name.hint')}
                        value={draft.name}
                        error={shown('name')}
                        required
                        autoFocus
                        onChange={(name) => set(!identifierTouched ? { name, identifier: suggestIdentifier(name) } : { name }, 'name')}
                    />
                    <TextField
                        label={t('field.identifier')}
                        hint={t('field.identifier.hint')}
                        value={draft.identifier}
                        error={shown('identifier')}
                        required
                        disabled={!isNew}
                        onChange={(identifier) => { setIdentifierTouched(true); set({ identifier }, 'identifier'); }}
                    />

                </Section>

                <Section title={t('section.addresses')} open={isOpen('addresses', filled, withProblems)}>
                    {draft.siteaccesses.length === 0 && isNew ? (
                        <div className="masilia-sites-suggest">
                            <p className="masilia-sites-muted">{t('rows.suggest.hint')}</p>
                            <OrderedChoice
                                label={t('sa.languages')}
                                options={data.options.languages.map((code) => ({ value: code, label: code }))}
                                value={suggestLanguages}
                                addPlaceholder={t('sa.languages.add')}
                                moveUpLabel={t('sa.up')}
                                moveDownLabel={t('sa.down')}
                                removeLabel={t('sa.language.remove')}
                                firstLabel={t('sa.languages.main')}
                                onChange={setSuggestLanguages}
                            />
                            <TextField label={t('rows.suggest.host')} hint={t('sa.host.hint')} value={suggestHost} onChange={setSuggestHost} />
                            <Button
                                type="secondary"
                                size="small"
                                disabled={suggestLanguages.length === 0 || suggestHost.trim() === ''}
                                onClick={() => { set({ siteaccesses: suggestSiteaccesses(draft.identifier !== '' ? draft.identifier : suggestIdentifier(draft.name), suggestLanguages, suggestHost.trim().toLowerCase()) }, 'siteaccesses'); }}
                            >{t('rows.suggest.apply')}</Button>
                        </div>
                    ) : null}
                    {shown('siteaccesses') !== undefined ? <p className="ibexa-form-error__row" role="alert">{shown('siteaccesses')}</p> : null}
                    <SiteaccessRows
                        path="siteaccesses"
                        rows={draft.siteaccesses}
                        languages={data.options.languages}
                        problems={submitted || touched.has('siteaccesses') ? problems : []}
                        onChange={(rows) => set({ siteaccesses: rows }, 'siteaccesses')}
                        template={(rows) => ({ host: rows[rows.length - 1]?.host ?? '' })}
                    />
                    {lines.length > 0 ? (
                        <div className="masilia-sites-addresses">
                            <h4>{t('addresses.title')}</h4>
                            <ul>{lines.map((line) => <li key={line.key}><code>{line.url}</code> <span className="masilia-sites-muted">{line.name} · {line.languages.join(' → ')}</span></li>)}</ul>
                        </div>
                    ) : null}

                </Section>

                <Section title={t('section.admins')} open={isOpen('admins', filled, withProblems)}>
                    <p className="masilia-sites-muted">{t('section.admins.hint')}</p>
                    <SiteaccessRows
                        path="adminSiteaccesses"
                        rows={draft.adminSiteaccesses}
                        languages={data.options.languages}
                        problems={submitted || touched.has('adminSiteaccesses') ? problems : []}
                        onChange={(rows) => set({ adminSiteaccesses: rows }, 'adminSiteaccesses')}
                        template={(rows) => ({ languages: draft.siteaccesses[0]?.languages ?? [], host: rows[rows.length - 1]?.host ?? '' })}
                    />

                </Section>

                <Section title={t('section.settings')} open={isOpen('settings', filled, withProblems)}>
                    <UdwPicker
                        label={t('field.root')}
                        hint={t('field.root.hint')}
                        config={udw.root}
                        title={t('field.root.title')}
                        value={view(draft.rootLocationId, rootView, `#${draft.rootLocationId ?? ''}`)}
                        error={shown('rootLocationId')}
                        required
                        chooseLabel={t('field.picker.choose')}
                        changeLabel={t('field.picker.change')}
                        clearLabel={t('field.picker.clear')}
                        emptyLabel={t('field.picker.empty')}
                        unavailableLabel={t('field.picker.unavailable')}
                        onPick={(picked) => set({ rootLocationId: picked.id }, 'rootLocationId')}
                        onClear={() => set({ rootLocationId: null }, 'rootLocationId')}
                    />
                    <DropdownField
                        label={t('field.design')}
                        hint={t('field.design.hint')}
                        value={draft.design}
                        error={shown('design')}
                        required
                        placeholder={t('field.design.placeholder')}
                        options={data.options.designs.map((d) => ({ value: d, label: d }))}
                        onChange={(design) => set({ design }, 'design')}
                    />
                    <DropdownField
                        label={t('field.base')}
                        hint={t('field.base.hint')}
                        value={draft.base}
                        error={shown('base')}
                        emptyLabel={t('field.base.none')}
                        options={data.options.baseGroups.map((b) => ({ value: b, label: b }))}
                        onChange={(base) => set({ base }, 'base')}
                    />
                    <UdwPicker
                        label={t('field.layout')}
                        hint={t('field.layout.hint')}
                        config={udw.layout}
                        title={t('field.layout.title')}
                        value={draft.layoutRemoteId === '' ? null : { id: layoutView?.id ?? 0, name: layoutView?.name ?? draft.layoutRemoteId, path: layoutView?.path ?? [] }}
                        error={shown('layoutRemoteId')}
                        chooseLabel={t('field.picker.choose')}
                        changeLabel={t('field.picker.change')}
                        clearLabel={t('field.picker.clear')}
                        emptyLabel={t('field.picker.empty')}
                        unavailableLabel={t('field.picker.unavailable')}
                        onPick={(picked) => set({ layoutRemoteId: picked.remoteId }, 'layoutRemoteId')}
                        onClear={() => set({ layoutRemoteId: '' }, 'layoutRemoteId')}
                    />
                </Section>

                <Section title={t('section.advanced')} open={isOpen('advanced', filled, withProblems)}>
                    <TextField label={t('field.var_dir')} hint={t('field.var_dir.hint')} value={draft.varDir} error={shown('varDir')} onChange={(varDir) => set({ varDir }, 'varDir')} />
                    <TextField label={t('field.cookie')} hint={t('field.cookie.hint')} value={draft.sessionCookieDomain} error={shown('sessionCookieDomain')} onChange={(sessionCookieDomain) => set({ sessionCookieDomain }, 'sessionCookieDomain')} />
                    {cookieSuggestion !== null && draft.sessionCookieDomain !== cookieSuggestion ? (
                        <p><Button type="tertiary" size="small" onClick={() => set({ sessionCookieDomain: cookieSuggestion }, 'sessionCookieDomain')}>{t('field.cookie.use', { domain: cookieSuggestion })}</Button></p>
                    ) : null}
                    <DropdownField
                        label={t('field.admin_siteaccess')}
                        hint={t('field.admin_siteaccess.hint')}
                        value={draft.adminSiteaccess}
                        error={shown('adminSiteaccess')}
                        emptyLabel={t('field.base.none')}
                        options={data.options.adminSiteaccesses.map((a) => ({ value: a, label: a }))}
                        onChange={(adminSiteaccess) => set({ adminSiteaccess }, 'adminSiteaccess')}
                    />
                </Section>

            </Dialog>
            {discarding ? (
                <ConfirmDialog title={t('confirm.discard.title')} confirmLabel={t('action.discard')} cancelLabel={t('action.cancel')} danger onConfirm={onClose} onCancel={() => setDiscarding(false)}>
                    <p>{t('confirm.discard.text')}</p>
                </ConfirmDialog>
            ) : null}
        </>
    );
}
