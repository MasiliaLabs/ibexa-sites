import { createTranslator } from '../shared/i18n';

/** The texts of the sites page, in English: what shows when the admin has no translation of a key (see the translation files of the bundle). */
export const DEFAULTS: Readonly<Record<string, string>> = {
    // Checks of a site while it is written (the server checks again, and has the last word)
    'validation.identifier': 'The identifier is 2 to 30 characters: lower case letters, digits and "_", starting with a letter.',
    'validation.identifier.taken': 'A site "%identifier%" already exists.',
    'validation.name': 'The name is required, 100 characters at most.',
    'validation.root.required': 'Choose the location where the site starts.',
    'validation.root.used': 'This location is already the root of another site.',
    'validation.design': 'Choose a design.',
    'validation.base': '"%base%" is not a group a site can take its settings from.',
    'validation.var_dir': 'A relative path of lower case letters, digits, "-" and "_" ("var/demo").',
    'validation.layout': 'Letters, digits, "-" and "_" only, 100 at most.',
    'validation.cookie.invalid': 'Not a cookie domain: lower case, at least two labels, like ".example.org".',
    'validation.cookie.cover': 'The cookie domain does not cover the host "%host%": the session would not be shared.',
    'validation.siteaccesses.none': 'A site needs at least one siteaccess.',
    'validation.sa.name': '2 to 40 characters: lower case letters, digits and "_", starting with a letter.',
    'validation.sa.name.twice': 'This name is given twice.',
    'validation.sa.name.taken': 'This name is already used by another site.',
    'validation.sa.host': 'Not a host name (lower case, like "demo.example.org").',
    'validation.sa.prefix': 'Lower case letters, digits and "-", without slash.',
    'validation.sa.languages.none': 'Choose at least one language.',
    'validation.sa.languages.twice': 'A language is given twice.',
    'validation.sa.languages.unknown': 'The language "%language%" does not exist in Ibexa.',
    'validation.sa.address.twice': 'Two siteaccesses answer on this address.',
    'validation.sa.address.taken': 'This address is already used by another site.',
    'validation.sa.bare': 'Only one siteaccess can be at the root of this host: give the others a prefix.',
};

export const DOMAIN = 'masilia_ibexa_sites';

/** The translator of the page. */
export const t = createTranslator(DEFAULTS, DOMAIN);
