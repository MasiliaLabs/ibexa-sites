import React, { useId, useRef } from 'react';
import type { ReactNode } from 'react';

import { tabAfterKey } from './tabsNav';

export interface TabItem {
    id: string;
    label: string;
    content: ReactNode;
}

interface TabsProps {
    tabs: TabItem[];
    active: string;
    onChange: (id: string) => void;
    label: string;
}

/** The admin's tabs: the rounded tab with its corner, and the panel of the active one below. */
export function Tabs({ tabs, active, onChange, label }: TabsProps): React.ReactElement {
    const group = useId();
    const refs = useRef(new Map<string, HTMLAnchorElement>());
    const ids = tabs.map((tab) => tab.id);

    const onKeyDown = (event: React.KeyboardEvent): void => {
        const next = tabAfterKey(ids, active, event.key);
        if (next !== null) {
            event.preventDefault();
            onChange(next);
            refs.current.get(next)?.focus();
        }
    };

    return (
        <>
            <div className="ibexa-header">
                <div className="ibexa-tabs">
                    <ul className="nav nav-tabs ibexa-tabs__list" role="tablist" aria-label={label} onKeyDown={onKeyDown}>
                        {tabs.map((tab) => {
                            const isActive = tab.id === active;

                            return (
                                <li className={`nav-item ibexa-tabs__tab${isActive ? ' ibexa-tabs__tab--active' : ''}`} key={tab.id} role="presentation">
                                    <a
                                        ref={(element) => { if (element !== null) { refs.current.set(tab.id, element); } else { refs.current.delete(tab.id); } }}
                                        id={`${group}-tab-${tab.id}`}
                                        className={`nav-link ibexa-tabs__link${isActive ? ' active' : ''}`}
                                        href={`#${group}-panel-${tab.id}`}
                                        role="tab"
                                        aria-selected={isActive}
                                        aria-controls={`${group}-panel-${tab.id}`}
                                        tabIndex={isActive ? 0 : -1}
                                        onClick={(event) => { event.preventDefault(); onChange(tab.id); }}
                                    >
                                        {tab.label}
                                    </a>
                                    <svg className="ibexa-tabs__tab-corner" width="44" height="50" viewBox="0 0 44 50" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path className="ibexa-svg-tab-corner-border" d="M0.0436401 1H0.458984C11.8221 1 22.21 7.42007 27.2918 17.5836L43 49H0.0436401" strokeWidth="2" />
                                        <path className="ibexa-svg-tab-corner-inside" d="M0 49V1C14.8 0.600001 23.8333 10.8333 26.5 16L43 49H0Z" />
                                    </svg>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </div>
            <div className="tab-content ibexa-tab-content">
                {tabs.map((tab) => (
                    <div
                        key={tab.id}
                        id={`${group}-panel-${tab.id}`}
                        className={`tab-pane ibexa-tab-content__pane${tab.id === active ? ' active' : ''}`}
                        role="tabpanel"
                        aria-labelledby={`${group}-tab-${tab.id}`}
                        hidden={tab.id !== active}
                    >
                        {tab.id === active ? tab.content : null}
                    </div>
                ))}
            </div>
        </>
    );
}
