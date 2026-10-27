<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/scripts')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests/integration');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder);
