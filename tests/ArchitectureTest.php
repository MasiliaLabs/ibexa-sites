<?php

declare(strict_types=1);

namespace Masilia\IbexaSites\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The layout of the Ibexa packages: the bundle is the integration with Symfony, the lib is the implementation, the contracts are
 * the public API. Each holds what it should and knows only what it may.
 */
final class ArchitectureTest extends TestCase
{
    private const SRC = __DIR__ . '/../src';

    /** What a bundle may hold: the integration, nothing else. */
    private const BUNDLE_MAY_HOLD = ['Command', 'Controller', 'DependencyInjection', 'EventListener', 'Migrations', 'Resources', 'Security', 'MasiliaIbexaSitesBundle.php'];

    /** What the lib may not name: the bundle, and the parts of Symfony that wire or serve an application. */
    private const LIB_MAY_NOT_USE = [
        'Masilia\\Bundle\\',
        'Symfony\\Component\\HttpKernel\\',
        'Symfony\\Bundle\\',
        'Symfony\\Component\\Config\\',
        'Symfony\\Component\\DependencyInjection\\ContainerBuilder',
        'Symfony\\Component\\DependencyInjection\\Extension\\',
        'Symfony\\Component\\DependencyInjection\\Compiler\\',
        'Symfony\\Component\\DependencyInjection\\Loader\\',
        'Symfony\\Component\\HttpFoundation\\',
    ];

    /** @return list<string> */
    private function files(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::SRC . '/' . $directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /** @return list<string> */
    private function uses(string $path): array
    {
        preg_match_all('/^use ([A-Za-z0-9_\\\\]+)/m', (string) file_get_contents($path), $uses);

        return $uses[1];
    }

    public function testTheBundleHoldsTheSymfonyIntegrationOnly(): void
    {
        $found = array_values(array_diff(scandir(self::SRC . '/bundle') ?: [], ['.', '..']));

        self::assertSame([], array_values(array_diff($found, self::BUNDLE_MAY_HOLD)), 'The bundle holds what is not integration: it belongs in src/lib.');
    }

    public function testTheLibKnowsNeitherTheBundleNorTheWiringOfSymfony(): void
    {
        $wrong = [];
        foreach ($this->files('lib') as $path) {
            foreach ($this->uses($path) as $name) {
                foreach (self::LIB_MAY_NOT_USE as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        $wrong[] = sprintf('%s uses %s', substr($path, strlen(self::SRC) + 1), $name);
                    }
                }
            }
        }

        self::assertSame([], $wrong, implode("\n", $wrong));
    }

    public function testTheContractsAreThePublicApiAndKnowNoImplementation(): void
    {
        $wrong = [];
        foreach ($this->files('contracts') as $path) {
            foreach ($this->uses($path) as $name) {
                if (str_starts_with($name, 'Masilia\\IbexaSites\\') || str_starts_with($name, 'Masilia\\Bundle\\') || str_starts_with($name, 'Doctrine\\') || str_starts_with($name, 'Symfony\\')) {
                    $wrong[] = sprintf('%s uses %s', substr($path, strlen(self::SRC) + 1), $name);
                }
            }
        }

        self::assertSame([], $wrong, implode("\n", $wrong));
    }
}
