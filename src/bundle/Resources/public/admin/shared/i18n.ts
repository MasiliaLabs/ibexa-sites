/**
 * The texts of a screen: the translation of the admin (Bazinga's `Translator`, the domain of the bundle) when it has one, the
 * English default written next to the key otherwise. A key the translator does not know comes back as itself: that is "no
 * translation", and the default is used.
 */
interface AdminTranslator {
    trans: (id: string, parameters?: Record<string, string | number>, domain?: string) => string;
}

export type Translate = (key: string, parameters?: Record<string, string | number>) => string;

function fill(text: string, parameters: Record<string, string | number>): string {
    return Object.entries(parameters).reduce((out, [name, value]) => out.split(`%${name}%`).join(String(value)), text);
}

export function createTranslator(defaults: Readonly<Record<string, string>>, domain: string, translator?: AdminTranslator): Translate {
    return (key, parameters = {}) => {
        const admin = translator ?? (globalThis as { Translator?: AdminTranslator }).Translator;
        const translated = admin !== undefined ? admin.trans(key, parameters, domain) : key;

        return translated !== key && translated !== '' ? translated : fill(defaults[key] ?? key, parameters);
    };
}
