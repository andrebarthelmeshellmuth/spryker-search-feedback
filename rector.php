<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\ConvertStaticToSelfRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/tools',
    ])
    ->withSkip([
        // The bare directory pattern alone doesn't reliably skip the FILES inside it -- fnmatch() needs
        // an exact string match, and a per-file path has a filename trailing the directory this pattern
        // matches. Confirmed empirically on the sibling search-ranking package: this let a dead-code
        // rector reach into regenerated *TesterActions.php files. Both forms kept since which one
        // actually matches depends on how the caller passes the path.
        __DIR__ . '/tests/*/_support/_generated',
        __DIR__ . '/tests/*/_support/_generated/*',
        __DIR__ . '/tests/*/_output',
        __DIR__ . '/tests/*/_data',
        // Freshly generated each CI run for the standalone portable-tests job, gitignored, never
        // committed — same reason src/Generated/ is never touched in a real Spryker project either.
        __DIR__ . '/src/Generated',
        __DIR__ . '/src/Generated/*',
        // Bridge classes are Spryker's generated dependency-glue boilerplate — real Spryker core Bridges
        // still ship the classic `@var` + assign-in-constructor form, not promoted properties. Promoting
        // these would permanently diverge from what the Spryker code generator itself produces, so a
        // future regeneration would just revert it.
        ClassPropertyAssignToConstructorPromotionRector::class => [
            __DIR__ . '/src/*Bridge.php',
        ],
        // Spryker.Commenting.DocBlockParam (active via the base Spryker ruleset, phpcs.xml) requires
        // exactly one @param tag per method parameter, typed or not. This rule strips @param tags for
        // natively typed params — a direct, systemic contradiction of that convention. Same rule, same
        // reasoning, already skipped in the sibling search-debug/search-ranking packages.
        RemoveUselessParamTagRector::class,
        // Typed class constants (PHP 8.3) aren't understood by the installed phpcs 3.7.1
        // (Generic.NamingConventions.UpperCaseConstantName misreads the type as the constant name).
        // Same bug already documented and skipped for this exact rule in the sibling packages.
        AddTypeToConstRector::class,
        // Rewrites plain `=== null` / `!== null` checks on a nullable single-class type into
        // `instanceof \Fully\Qualified\ClassName` — strictly more verbose for a simple null check,
        // breaks this codebase's consistent === null idiom, and writes an inline FQCN instead of a use
        // import, which trips Spryker.Namespaces.UseStatement. Same rule, same reasoning, already
        // skipped in the sibling packages.
        FlipTypeControlToUseExclusiveTypeRector::class,
        // Direct contradiction of Spryker's own SprykerPreferStaticOverSelf sniff (active, not
        // excluded): converts static:: to self::. Same rule, same reasoning, already skipped in the
        // sibling packages.
        ConvertStaticToSelfRector::class,
        // TicketManager::changeTicketStatus()'s final `@var ... $ticketTransfer` + assignment is not
        // decorative: it narrows repository->findTicketById()'s nullable return to the non-nullable type
        // this method promises, and that narrowing is only safe because of the existence check earlier in
        // the same method — a connection Rector's static analysis can't see across. Inlining the return
        // (as both rules suggest) would silently drop the one annotation telling phpstan the narrowing is
        // intentional, reintroducing a real level-8 nullable-mismatch error on the next host-shop phpstan
        // run. Scoped to this one file — the identical pattern in SearchFeedbackDependencyProvider.php
        // (a plain Container return, no nullable narrowing involved) was correctly left simplified.
        SimplifyUselessVariableRector::class => [
            __DIR__ . '/src/SprykerCommunity/Zed/SearchFeedback/Business/Ticket/TicketManager.php',
        ],
        RemoveUselessVarTagRector::class => [
            __DIR__ . '/src/SprykerCommunity/Zed/SearchFeedback/Business/Ticket/TicketManager.php',
        ],
    ])
    // Picks up the PHP floor (>=8.3) from composer.json.
    ->withPhpSets()
    ->withDeadCodeLevel(69)
    ->withCodeQualityLevel(75)
    ->withoutParallel();
