<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/public')
    ->in(__DIR__ . '/scripts')
    ->in(__DIR__ . '/tests')
    ->append([__DIR__ . '/router.php'])
    ->exclude([
        'vendor',
        'cache',
        'caches',
        'logs',
        'log',
        'exports',
        'generated',
        'build',
        'dist',
    ])
    ->notName('/\.log$/')
    ->notName('/\.sql$/')
    ;

return (new PhpCsFixer\Config())
    ->setCacheFile(sys_get_temp_dir() . '/dnd-portal-php-cs-fixer.cache')
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'braces_position' => [
            'classes_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line',
            'anonymous_functions_opening_brace' => 'same_line',
            'anonymous_classes_opening_brace' => 'same_line',
        ],
        'class_attributes_separation' => ['elements' => ['method' => 'one']],
        'array_syntax' => ['syntax' => 'short'],
        'single_line_empty_body' => true,
        'no_multiple_statements_per_line' => true,
        'binary_operator_spaces' => ['default' => 'single_space'],
        'concat_space' => ['spacing' => 'one'],
        'cast_spaces' => ['space' => 'single'],
        'type_declaration_spaces' => ['elements' => ['function', 'property']],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'function_declaration' => ['trailing_comma_single_line' => false],
        'method_chaining_indentation' => true,
        'operator_linebreak' => ['only_booleans' => true, 'position' => 'beginning'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder);
