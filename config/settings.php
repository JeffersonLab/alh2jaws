<?php
return [

    /*
    |--------------------------------------------------------------------------
    | ALH Source Directory
    |--------------------------------------------------------------------------
    |
    | This option defines where ALH files containing alarm definitions will be found.
    |
    */
    'alh_source_directory' => env('ALH_SOURCE_DIRECTORY', '/cs/prohome/apps/m/makeALHConfig/4-1/src/bin/JAWS'),


    /*
    |--------------------------------------------------------------------------
    | JAWS API BASE
    |--------------------------------------------------------------------------
    |
    | This option defines the base url for constructing JAWS API REST endpoint urls.
    |
    */
    'jaws_api_base' => env('JAWS_API_BASE', 'https://ace.jlab.org/jaws/ajax'),



];
