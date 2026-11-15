import React, { useId } from 'react';

import { IconSvg } from './IconSvg';

interface SearchInputProps {
    value: string;
    onChange: (value: string) => void;
    placeholder: string;
    clearLabel: string;
}

/** A search field with the magnifier on its right and a clear button once there is something to clear (the admin's own markup). */
export function SearchInput({ value, onChange, placeholder, clearLabel }: SearchInputProps): React.ReactElement {
    const id = useId();

    return (
        <div className="ibexa-input-text-wrapper ibexa-input-text-wrapper--search ibexa-input-text-wrapper--type-text masilia-kit-search">
            <div className="ibexa-input-text-wrapper__input-wrapper">
                <input
                    id={id}
                    className="ibexa-input ibexa-input--text form-control"
                    type="text"
                    value={value}
                    placeholder={placeholder}
                    aria-label={placeholder}
                    onChange={(event) => onChange(event.target.value)}
                />
                <div className="ibexa-input-text-wrapper__actions">
                    {value !== '' ? (
                        <button type="button" className="btn ibexa-btn ibexa-btn--ghost ibexa-btn--no-text ibexa-input-text-wrapper__action-btn" title={clearLabel} aria-label={clearLabel} onClick={() => onChange('')}>
                            <IconSvg name="discard" size="small" />
                        </button>
                    ) : null}
                    <span className="ibexa-input-text-wrapper__action-btn ibexa-input-text-wrapper__action-btn--search masilia-kit-search__icon" aria-hidden="true">
                        <IconSvg name="search" />
                    </span>
                </div>
            </div>
        </div>
    );
}
