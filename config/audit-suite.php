<?php

return [
    'enabled' => env('AUDIT_ENABLED', true),
    
    'auditors' => [
        'seo' => env('AUDIT_SEO', true),
        'security' => env('AUDIT_SECURITY', true),
        'performance' => env('AUDIT_PERFORMANCE', true),
        'database' => env('AUDIT_DATABASE', true),
        'code_quality' => env('AUDIT_CODE_QUALITY', true),
        'infrastructure' => env('AUDIT_INFRASTRUCTURE', true),
    ],
    
    'store_results' => env('AUDIT_STORE_RESULTS', true),
    'max_stored_audits' => 50,
    
    'scoring' => [
        'weights' => [
            'security' => 35,
            'performance' => 25,
            'seo' => 20,
            'code_quality' => 12,
            'database' => 5,
            'infrastructure' => 3,
        ],
        'critical_threshold' => 0,  // Fail si N criticals
        'high_threshold' => 5,       // Warn si > N highs
        'minimum_score' => 70,       // Score minimum acceptable
    ],
    
    'notifications' => [
        'slack' => env('SLACK_WEBHOOK_URL'),
        'email' => env('AUDIT_EMAIL'),
        'discord' => env('DISCORD_WEBHOOK_URL'),
    ],
    
    'reporting' => [
        'include_evidence' => true,
        'include_suggestions' => true,
        'include_documentation' => true,
    ],
    
    'cache' => [
        'ttl' => 3600,
        'driver' => 'redis',
    ],
];

