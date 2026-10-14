<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product storage file
    |--------------------------------------------------------------------------
    |
    | Submitted products are stored as JSON in this file instead of a database.
    |
    */

    'path' => env('PRODUCTS_FILE', storage_path('app/products.json')),

];
