<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CMS Core Configuration
    |--------------------------------------------------------------------------
    |
    | Defines configuration parameters for plugin directories, discovery,
    | emergency safe mode and registry behavior.
    |
    */

    'plugins' => [
        'path' => env('CMS_PLUGINS_PATH', base_path('platform/plugins')),
        'cache_path' => env('CMS_PLUGINS_CACHE', storage_path('framework/cache/cms_plugins.php')),
        'autoload' => env('CMS_PLUGINS_AUTOLOAD', true),
    ],

    'safe_mode' => [
        'enabled' => env('CMS_SAFE_MODE', false),
    ],

    'table_prefix' => env('CMS_TABLE_PREFIX', 'cms_'),
];
