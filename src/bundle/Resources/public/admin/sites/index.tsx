import React, { StrictMode } from 'react';
import type { ReactNode } from 'react';

import { SitesApp } from './SitesApp';

const ROOT_ID = 'masilia-sites-root';

interface ReactDomClient {
    createRoot: (container: Element) => { render: (node: ReactNode) => void };
}

function json(value: string | undefined): Record<string, unknown> {
    try {
        return JSON.parse(value ?? '{}') as Record<string, unknown>;
    } catch {
        return {};
    }
}

/**
 * Ibexa loads the React DOM client API as the global `ReactDOMClient` (react-dom-load); the `react-dom` default export has no
 * `createRoot` under React 19. The page hands over the configuration of the content browser (built by Twig).
 */
function mount(): void {
    const client = (window as unknown as { ReactDOMClient?: ReactDomClient }).ReactDOMClient;
    const container = document.getElementById(ROOT_ID);
    if (client === undefined || container === null) {
        return;
    }

    client.createRoot(container).render(
        <StrictMode>
            <SitesApp udw={{ root: json(container.dataset.udwRoot), layout: json(container.dataset.udwLayout) }} />
        </StrictMode>,
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
} else {
    mount();
}
