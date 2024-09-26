<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\DocCommentAlignmentSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\LanguageConstructSpacingSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\WhiteSpace\SuperfluousWhitespaceSniff;
use PhpCsFixer\Fixer\Alias\ModernizeStrposFixer;
use PhpCsFixer\Fixer\AttributeNotation\AttributeEmptyParenthesesFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\CastNotation\ModernizeTypesCastingFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\StaticLambdaFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\NoLeadingNamespaceWhitespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoUselessInheritdocFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderByValueFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocParamOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSingleLineVarSpacingFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocTagTypeFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitConstructFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderNameFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderReturnTypeFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDataProviderStaticFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDedicateAssertFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitDedicateAssertInternalTypeFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitExpectationFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitInternalClassFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMethodCasingFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMockFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitMockShortWillReturnFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitNamespacedFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestCaseStaticMethodCallsFixer;
use PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Semicolon\SpaceAfterSemicolonFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesAroundOffsetFixer;
use PhpCsFixer\Fixer\Whitespace\NoWhitespaceInBlankLineFixer;
use SlevomatCodingStandard\Sniffs\Attributes\AttributeAndTargetSpacingSniff;
use SlevomatCodingStandard\Sniffs\Attributes\AttributesOrderSniff;
use SlevomatCodingStandard\Sniffs\Attributes\DisallowAttributesJoiningSniff;
use SlevomatCodingStandard\Sniffs\Attributes\DisallowMultipleAttributesPerLineSniff;
use SlevomatCodingStandard\Sniffs\Attributes\RequireAttributeAfterDocCommentSniff;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
use Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
use Symplify\CodingStandard\Fixer\Spacing\MethodChainingNewlineFixer;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([__DIR__ . '/src']);

    $ecsConfig->rules(
        [
            NoUnusedImportsFixer::class,
            OrderedImportsFixer::class,
            FullyQualifiedStrictTypesFixer::class,
            GlobalNamespaceImportFixer::class,
            NoLeadingImportSlashFixer::class,
            StaticLambdaFixer::class,
            ReferenceUsedNamesOnlySniff::class,
            StandaloneLinePromotedPropertyFixer::class,
            BlankLineAfterOpeningTagFixer::class,
            CastSpacesFixer::class,
            SingleTraitInsertPerStatementFixer::class,
            FunctionTypehintSpaceFixer::class,
            NoBlankLinesAfterClassOpeningFixer::class,
            NoSinglelineWhitespaceBeforeSemicolonsFixer::class,
            NoLeadingNamespaceWhitespaceFixer::class,
            NoSpacesAroundOffsetFixer::class,
            NoWhitespaceInBlankLineFixer::class,
            ReturnTypeDeclarationFixer::class,
            SpaceAfterSemicolonFixer::class,
            TernaryOperatorSpacesFixer::class,
            LanguageConstructSpacingSniff::class,
            MethodArgumentSpaceFixer::class,
            UseArrowFunctionsFixer::class,
            NoSpacesAfterFunctionNameFixer::class,
            ModernizeStrposFixer::class,
            ModernizeTypesCastingFixer::class,
            FunctionDeclarationFixer::class,
            MethodChainingIndentationFixer::class,
            SingleQuoteFixer::class,
            StrictComparisonFixer::class
        ]
    );

    $ecsConfig->skip([
        MethodChainingNewlineFixer::class,
        PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer::class
    ]);

    $ecsConfig->ruleWithConfiguration(ClassAttributesSeparationFixer::class, [
        'elements' => [
            'property' => 'one',
            'method' => 'one',
        ],
    ]);
    $ecsConfig->ruleWithConfiguration(ConcatSpaceFixer::class, [
        'spacing' => 'one',
    ]);

    $ecsConfig->ruleWithConfiguration(SuperfluousWhitespaceSniff::class, [
        'ignoreBlankLines' => false,
    ]);
    $ecsConfig->ruleWithConfiguration(BinaryOperatorSpacesFixer::class, [
        'operators' => [
            '=>' => 'single_space',
            '=' => 'single_space',
        ],
    ]);

    $ecsConfig->sets([
        SetList::PHPUNIT,
        SetList::CLEAN_CODE,
        SetList::ARRAY,
        SetList::DOCBLOCK,
        SetList::NAMESPACES,
        SetList::COMMENTS,
        SetList::CONTROL_STRUCTURES,
        SetList::SYMPLIFY,
        SetList::STRICT,
        SetList::PSR_12,
    ]);

    $ecsConfig->ruleWithConfiguration(OrderedClassElementsFixer::class, [
        'order' => [
            'use_trait',
            'constant',
            'property',
            'construct',
            'destruct',
            'method_public_static',
            'magic',
            'method',
        ],
    ]);

    // PHPUnit
    $ecsConfig->rules([
        PhpUnitConstructFixer::class,
        PhpUnitDataProviderNameFixer::class,
        PhpUnitDataProviderReturnTypeFixer::class,
        PhpUnitDataProviderStaticFixer::class,
        PhpUnitDedicateAssertFixer::class,
        PhpUnitDedicateAssertInternalTypeFixer::class,
        PhpUnitExpectationFixer::class,
        PhpUnitInternalClassFixer::class,
        PhpUnitMethodCasingFixer::class,
        PhpUnitMockFixer::class,
        PhpUnitMockShortWillReturnFixer::class,
        PhpUnitNamespacedFixer::class,
        PhpUnitStrictFixer::class,
    ]);

    $ecsConfig->ruleWithConfiguration(PhpUnitTestCaseStaticMethodCallsFixer::class, [
        'call_type' => 'self',
    ]);

    // Doc blocks
    $ecsConfig->rules(
        [
            PhpdocSingleLineVarSpacingFixer::class,
            PhpdocIndentFixer::class,
            PhpdocOrderFixer::class,
            PhpdocParamOrderFixer::class,
            PhpdocOrderByValueFixer::class,
            DocCommentAlignmentSniff::class,
            PhpdocNoUselessInheritdocFixer::class,
            PhpdocAddMissingParamAnnotationFixer::class,
            PhpdocTagTypeFixer::class
        ]
    );
    $ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
        'annotations' => ['author', 'package', 'category'],
    ]);
    $ecsConfig->ruleWithConfiguration(PhpdocAlignFixer::class, [
        'align' => 'left',
    ]);

    // Attributes
    $ecsConfig->rules(
        [
            ClassAttributesSeparationFixer::class,
            AttributeEmptyParenthesesFixer::class,
            RequireAttributeAfterDocCommentSniff::class,
            DisallowMultipleAttributesPerLineSniff::class,
            DisallowAttributesJoiningSniff::class,
            AttributeAndTargetSpacingSniff::class,
        ]
    );
    $ecsConfig->ruleWithConfiguration(AttributesOrderSniff::class, [
        'orderAlphabetically' => true,
    ]);

    $ecsConfig->cacheDirectory('/tmp/ecs/src');
};
