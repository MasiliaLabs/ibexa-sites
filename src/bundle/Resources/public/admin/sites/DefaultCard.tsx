import React, { useEffect, useState } from 'react';

import { FilterCard, FilterSelect } from '@masilia-admin-kit/FilterCard';
import { notify } from '@masilia-admin-kit/notify';
import type { Api } from './api';
import { t } from './messages';

interface DefaultCardProps {
    api: Api;
    defaultSiteaccess: string | null;
    choices: string[];
    canManage: boolean;
    onSaved: () => void;
}

/** The siteaccess that answers when no host matches: chosen among the project's and the active sites'. */
export function DefaultCard({ api, defaultSiteaccess, choices, canManage, onSaved }: DefaultCardProps): React.ReactElement {
    const [value, setValue] = useState(defaultSiteaccess ?? '');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | undefined>(undefined);

    useEffect(() => {
        setValue(defaultSiteaccess ?? '');
    }, [defaultSiteaccess]);

    const save = async (): Promise<void> => {
        setSaving(true);
        const result = await api.setDefault(value);
        setSaving(false);
        if (result.ok) {
            setError(undefined);
            notify('success', t('flash.default'));
            onSaved();
        } else {
            setError(result.message);
        }
    };

    return (
        <section>
            <FilterCard
                actions={canManage ? <button type="button" className="btn ibexa-btn ibexa-btn--secondary ibexa-adaptive-filters__submit-btn" disabled={saving || value === (defaultSiteaccess ?? '')} onClick={() => void save()}>{t('action.save')}</button> : undefined}
            >
                <FilterSelect
                    label={t('default.label')}
                    value={value}
                    disabled={!canManage || saving}
                    emptyLabel={t('default.project')}
                    options={choices.map((name) => ({ value: name, label: name }))}
                    onChange={setValue}
                />
            </FilterCard>
            <p className="masilia-sites-muted">{t('default.help')}</p>
            {error !== undefined ? <p className="ibexa-alert ibexa-alert--error" role="alert">{error}</p> : null}
        </section>
    );
}
