import React from 'react';

import { TextField } from '@masilia-admin-kit/Field';
import { messageFor } from '@masilia-admin-kit/http';
import type { FieldProblem } from '@masilia-admin-kit/http';
import { Button } from '@masilia-admin-kit/ids';
import { OrderedChoice } from '@masilia-admin-kit/OrderedChoice';
import { Muted } from '@masilia-admin-kit/Layout';
import { addressOf } from './addresses';
import { t } from './messages';
import { emptyRow, moveRow, removeRow, updateRow } from './rows';
import type { SiteaccessDraft } from './types';

interface SiteaccessRowsProps {
    /** The path of the list in the problems of the server and of the page ("siteaccesses", "adminSiteaccesses") */
    path: 'siteaccesses' | 'adminSiteaccesses';
    rows: SiteaccessDraft[];
    onChange: (rows: SiteaccessDraft[]) => void;
    languages: string[];
    problems: readonly FieldProblem[];
    disabled?: boolean;
    /** What a new row starts with (a name, a host to follow the one above…) */
    template?: (rows: readonly SiteaccessDraft[]) => Partial<SiteaccessDraft>;
}

/** The siteaccesses of a site: a card each, with its language(s), its host and prefix, and the address that makes. */
export function SiteaccessRows({ path, rows, onChange, languages, problems, disabled, template }: SiteaccessRowsProps): React.ReactElement {
    const languageOptions = languages.map((code) => ({ value: code, label: code }));

    return (
        <div className="masilia-sites-rows">
            {rows.length === 0 ? <p><Muted>{t('rows.none')}</Muted></p> : null}
            {rows.map((row, index) => {
                const base = `${path}[${index}]`;
                const address = addressOf(row);

                return (
                    <fieldset className="masilia-sites-row" key={row.key} disabled={disabled}>
                        <div className="masilia-sites-row__head">
                            <strong>{row.name !== '' ? row.name : `#${index + 1}`}</strong>
                            <span className="masilia-sites-row__tools">
                                <Button type="tertiary" size="small" icon="caret-up" ariaLabel={t('sa.up')} title={t('sa.up')} disabled={index === 0} onClick={() => onChange(moveRow(rows, row.key, -1))} />
                                <Button type="tertiary" size="small" icon="caret-down" ariaLabel={t('sa.down')} title={t('sa.down')} disabled={index === rows.length - 1} onClick={() => onChange(moveRow(rows, row.key, 1))} />
                                <Button type="tertiary" size="small" icon="trash" ariaLabel={t('rows.remove')} title={t('rows.remove')} onClick={() => onChange(removeRow(rows, row.key))} />
                            </span>
                        </div>
                        <TextField label={t('sa.name')} hint={t('sa.name.hint')} value={row.name} error={messageFor(problems, `${base}.name`)} onChange={(name) => onChange(updateRow(rows, row.key, { name }))} required />
                        <OrderedChoice
                            label={t('sa.languages')}
                            hint={t('sa.languages.hint')}
                            options={languageOptions}
                            value={row.languages}
                            error={messageFor(problems, `${base}.languages`)}
                            addPlaceholder={t('sa.languages.add')}
                            moveUpLabel={t('sa.up')}
                            moveDownLabel={t('sa.down')}
                            firstLabel={t('sa.languages.main')}
                            onChange={(value) => onChange(updateRow(rows, row.key, { languages: value }))}
                            required
                        />
                        <div className="masilia-sites-row__address">
                            <TextField label={t('sa.host')} hint={t('sa.host.hint')} value={row.host} error={messageFor(problems, `${base}.host`)} onChange={(host) => onChange(updateRow(rows, row.key, { host }))} required />
                            <TextField label={t('sa.prefix')} hint={t('sa.prefix.hint')} value={row.uriPrefix} error={messageFor(problems, `${base}.uriPrefix`)} onChange={(uriPrefix) => onChange(updateRow(rows, row.key, { uriPrefix }))} />
                        </div>
                        <p className="masilia-sites-row__url" aria-live="polite">
                            <Muted>{t('sa.address')}: </Muted>
                            {address === '' ? <Muted>{t('addresses.empty')}</Muted> : <code>{address}</code>}
                        </p>
                    </fieldset>
                );
            })}
            <Button type="secondary" size="small" icon="create" disabled={disabled} onClick={() => onChange([...rows, emptyRow(template?.(rows) ?? {})])}>{t('rows.add')}</Button>
        </div>
    );
}

