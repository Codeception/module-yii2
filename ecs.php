<?php

declare(strict_types=1);

// ecs.php
use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ClassNotation\FinalInternalClassFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\NoBlankLinesAfterPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    // Parallel
    $ecsConfig->parallel();

    $ecsConfig->cacheDirectory('.ecs-cache');
    // Paths
    $ecsConfig->paths([
        __DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/ecs.php'
    ]);

    // A. full sets
    $ecsConfig->sets([SetList::PSR_12, SetList::SPACES, SetList::STRICT, SetList::DOCBLOCK]);

    $ecsConfig->rule(NotOperatorWithSuccessorSpaceFixer::class);
    $ecsConfig->rule(ArraySyntaxFixer::class);
    $ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
        'annotations' => ['author', 'inheritdoc', 'package']
    ]);
    $ecsConfig->rule(NoBlankLinesAfterPhpdocFixer::class);
    $ecsConfig->ruleWithConfiguration(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true
    ]);
    $ecsConfig->rule(NoEmptyPhpdocFixer::class);
    $ecsConfig->rule(NoUnusedImportsFixer::class);
    $ecsConfig->ruleWithConfiguration(FinalInternalClassFixer::class, [
        'annotation_exclude' => ['@not-fix', '@internal'],
        'annotation_include' => [],
        'consider_absent_docblock_as_internal_class' => true
    ]);
    $ecsConfig->ruleWithConfiguration(ForbiddenFunctionsSniff::class, [
        'forbiddenFunctions' => [
            'passthru' => null,
            'var_dump' => null,
        ]
    ]);
    $ecsConfig->rule(PhpdocIndentFixer::class);
    $ecsConfig->rule(\PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer::class);

    $ecsConfig->skip([
        ForbiddenFunctionsSniff::class => [
            'tests/**',
            'console/**'
        ],
        'tests/_support/_generated'
    ]);

    //    $ecsConfig->skip([
    //        FinalClassFixer::class => [
    //            'tests/**'
    //        ]
    //    ]);
};
