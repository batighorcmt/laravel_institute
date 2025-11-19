<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel Excel Configuration
    |--------------------------------------------------------------------------
    | Minimal config file to provide safe defaults for imports/exports and
    | temporary file handling. Adjust as needed; you can publish the package
    | config if the package exposes a vendor publishable config in your
    | environment.
    */

    'exports' => [
        'chunk_size' => 1000,
    ],

    'imports' => [
        // Number of rows to read per chunk when chunk reading is enabled
        'read_chunk_size' => 1000,
        // When using ToModel/OnEachRow imports, this controls batch size
        'batch_size' => 1000,
    ],

    'temporary_files' => [
        'local_path' => storage_path('framework/laravel-excel'),
        // You can set a cloud disk name here to use remote temporary storage
        'remote_disk' => env('EXCEL_TEMP_DISK', null),
    ],

    // Reader options - empty by default, set stream wrappers or readers here
    'reader' => [
        // 'excel' => [ /* reader specific options */ ],
    ],

    // Additional package options can be added here as needed.
];
