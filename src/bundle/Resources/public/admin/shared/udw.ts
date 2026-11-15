/**
 * The Universal Discovery Widget of the admin, to browse the content tree and take a location.
 *
 * The widget is the admin's own (window.ibexa.modules.UniversalDiscovery), shown full screen in the `#react-udw` container of
 * the layout. It needs the configuration Twig builds with ibexa_udw_config() (without its tabs and options it fails): that is
 * what a page hands to the script. This file has no dependency on React: whoever calls it gives the function that makes an element.
 */

/** A location as the widget answers (a REST location): only what is used here. */
export interface UdwLocation {
    id: number;
    pathString: string;
    ContentInfo: { Content: { _id: number; _remoteId: string; TranslatedName: string } };
}

/** What a script keeps of a location the person took. */
export interface PickedLocation {
    id: number;
    pathString: string;
    contentId: number;
    remoteId: string;
    name: string;
}

export function toPicked(location: UdwLocation): PickedLocation {
    const content = location.ContentInfo.Content;

    return { id: location.id, pathString: location.pathString, contentId: content._id, remoteId: content._remoteId, name: content.TranslatedName };
}

export interface UdwRoot {
    render: (node: unknown) => void;
    unmount: () => void;
}

/** What opening the widget needs from the page. */
export interface UdwHost {
    createRoot: (container: Element) => UdwRoot;
    module: unknown;
    container: Element;
}

/** The host the admin page offers, or null when the page has no widget (not the admin, or its scripts are not loaded yet). */
export function udwHost(): UdwHost | null {
    const global = globalThis as unknown as { ReactDOMClient?: { createRoot: (container: Element) => UdwRoot }; ibexa?: { modules?: { UniversalDiscovery?: unknown } } };
    const container = typeof document === 'undefined' ? null : document.getElementById('react-udw');
    const module = global.ibexa?.modules?.UniversalDiscovery;
    if (global.ReactDOMClient === undefined || module === undefined || container === null) {
        return null;
    }

    return { createRoot: (element) => global.ReactDOMClient!.createRoot(element), module, container };
}

export interface OpenOptions {
    /** The configuration of the page for this widget (ibexa_udw_config), as an object */
    config: Record<string, unknown>;
    title: string;
    /** Where it starts, when the person already took a location */
    startingLocationId?: number;
    onConfirm: (picked: PickedLocation) => void;
    onCancel?: () => void;
}

/** Opens the widget; returns what closes it. The first location the person confirmed is the one taken. */
export function openUdw(host: UdwHost, options: OpenOptions, createElement: (type: unknown, props: Record<string, unknown>) => unknown): () => void {
    const root = host.createRoot(host.container);
    let closed = false;
    const close = (): void => {
        if (!closed) {
            closed = true;
            root.unmount();
        }
    };
    root.render(createElement(host.module, {
        ...options.config,
        ...(options.startingLocationId !== undefined ? { startingLocationId: options.startingLocationId } : {}),
        multiple: false,
        title: options.title,
        onConfirm: (locations: UdwLocation[]) => {
            const first = locations[0];
            close();
            if (first !== undefined) {
                options.onConfirm(toPicked(first));
            }
        },
        onCancel: () => {
            close();
            options.onCancel?.();
        },
    }));

    return close;
}
