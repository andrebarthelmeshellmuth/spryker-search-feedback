<?php

/**
 * This file is part of the spryker-community/search-feedback package.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

declare(strict_types = 1);

/**
 * Verifies that this package's declared composer floors are REAL rather than guessed.
 *
 * Run via `composer check-floors`, which first resolves every declared constraint down to its oldest
 * allowed version (`--prefer-lowest --prefer-stable --no-dev`) and then executes this script against
 * that tree. Any symbol `src/` references but which does not exist at those versions means a declared
 * floor is too low — the package would fatal on a shop that legitimately installed the versions we claim
 * to support.
 *
 * Production dependencies only (`--no-dev`): dev tooling is irrelevant to what an adopter installs.
 */

$packageDir = dirname(__DIR__);

if (!is_file($packageDir . '/vendor/autoload.php')) {
    fwrite(STDERR, "No vendor/autoload.php — run `composer check-floors` rather than this script directly.\n");

    exit(1);
}

require $packageDir . '/vendor/autoload.php';

/**
 * Generated at build time by the host project — `Generated\` by `transfer:generate`, `Orm\` by
 * `propel:install`/`propel:model:build` from this package's own shipped schema.xml (merged with the
 * host's other schemas) — never shipped in any vendor tree, so their absence here is expected and says
 * nothing about dependency floors.
 *
 * @var array<string>
 */
const HOST_GENERATED_PREFIXES = [
    'Generated\\',
    'Orm\\',
];

/**
 * @var string
 */
const OWN_CODE_PREFIX = 'SprykerCommunity\\';

$usedSymbols = [];
$sourceFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($packageDir . '/src'));

foreach ($sourceFiles as $sourceFile) {
    if (!$sourceFile->isFile() || $sourceFile->getExtension() !== 'php') {
        continue;
    }

    preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)/m', (string)file_get_contents($sourceFile->getPathname()), $matches);

    foreach ($matches[1] as $symbol) {
        $usedSymbols[$symbol] = true;
    }
}

ksort($usedSymbols);

$missing = [];
$hostGenerated = 0;
$resolved = 0;

foreach (array_keys($usedSymbols) as $symbol) {
    if (str_starts_with($symbol, OWN_CODE_PREFIX)) {
        continue;
    }

    $isHostGenerated = false;

    foreach (HOST_GENERATED_PREFIXES as $hostGeneratedPrefix) {
        if (str_starts_with($symbol, $hostGeneratedPrefix)) {
            $isHostGenerated = true;

            break;
        }
    }

    if ($isHostGenerated) {
        $hostGenerated++;

        continue;
    }

    if (class_exists($symbol) || interface_exists($symbol) || trait_exists($symbol)) {
        $resolved++;

        continue;
    }

    $missing[] = $symbol;
}

printf(
    "floor-check: %d resolved | %d host-generated (skipped) | %d MISSING\n",
    $resolved,
    $hostGenerated,
    count($missing),
);

foreach ($missing as $symbol) {
    echo "  MISSING: $symbol\n";
}

if ($missing !== []) {
    fwrite(STDERR, "\nA declared floor is too low, or a dependency is undeclared entirely.\n");

    exit(1);
}

echo "All required symbols exist at the lowest allowed dependency versions.\n";

exit(0);
