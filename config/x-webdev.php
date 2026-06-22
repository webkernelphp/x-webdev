<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Monorepo root
    |--------------------------------------------------------------------------
    | Absolute path to the project root (where .git and composer.json live).
    */
    'monorepo_root' => webapp_path(),

    /*
    |--------------------------------------------------------------------------
    | Packages directory
    |--------------------------------------------------------------------------
    | Path, relative to monorepo_root, where source packages live.
    */
    'packages_dir' => 'packages',

    /*
    |--------------------------------------------------------------------------
    | Dev packages
    |--------------------------------------------------------------------------
    | x-* packages whose src/Commands are registered under {slug}:{command}.
    | Only list packages that are installed and expose dev commands.
    */
    'dev_packages' => [
        'x-monorepo',
        'x-webdev',
    ],

    /*
    |--------------------------------------------------------------------------
    | Package creation defaults
    |--------------------------------------------------------------------------
    */
    'default_vendor' => 'webkernel',
    'default_namespace' => 'Webkernel',
    'default_license' => 'EPL-2.0',
    'github_org' => 'webkernelphp',

    /*
    |--------------------------------------------------------------------------
    | PHP constraint for generated composer.json (require.php)
    |--------------------------------------------------------------------------
    | Do NOT put this value in config.platform.php — Packagist rejects constraints
    | there (use an exact version like 8.4.0 only in local root composer.json).
    */
    'default_php_constraint' => '>=8.4',
];
