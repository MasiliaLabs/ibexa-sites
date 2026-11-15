// Test-only resolver: lets extensionless relative imports (the webpack/TypeScript convention) load .ts files in Node, and maps the
// Encore alias "@masilia-admin-kit" to the sources of the kit (the sibling package of the monorepo, or MASILIA_ADMIN_KIT).
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const ALIAS = '@masilia-admin-kit/';
const kit = process.env.MASILIA_ADMIN_KIT
    ?? path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../../../../../../ibexa-admin-kit/src/bundle/Resources/public/admin');

export async function resolve(specifier, context, nextResolve) {
    if (specifier.startsWith(ALIAS)) {
        const target = pathToFileURL(path.join(kit, specifier.slice(ALIAS.length))).href;

        return nextResolve(/\.[cm]?[jt]sx?$/.test(target) ? target : `${target}.ts`, context);
    }
    try {
        return await nextResolve(specifier, context);
    } catch (error) {
        const hasExtension = /\.[cm]?[jt]sx?$/.test(specifier);
        if (error?.code === 'ERR_MODULE_NOT_FOUND' && specifier.startsWith('.') && !hasExtension) {
            return nextResolve(`${specifier}.ts`, context);
        }
        throw error;
    }
}
