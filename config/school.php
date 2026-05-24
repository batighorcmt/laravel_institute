<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Current School ID
    |--------------------------------------------------------------------------
    |
    | This value is set dynamically by the IdentifySchool middleware based on
    | the request domain. For local development you can set a default here
    | so the frontend page loads without a matching domain record.
    |
    */
    'id' => env('SCHOOL_ID', null),
];
