<?php

return [

    'name' => env('ELITEFIT_NAME', 'ARENA GYM'),
    'city' => env('ELITEFIT_CITY', 'Abidjan'),
    'country' => env('ELITEFIT_COUNTRY', 'Côte d\'Ivoire'),
    'currency' => 'FCFA',
    'super_admin_email' => env('ARENA_SUPER_ADMIN_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Salle unique (mode EliteFit)
    |--------------------------------------------------------------------------
    */
    'gym_id' => env('ELITEFIT_GYM_ID'),
    'gym_slug' => env('ELITEFIT_GYM_SLUG', 'elitefit-abidjan'),

    'walk_in_price' => (int) env('ELITEFIT_WALK_IN_PRICE', 3500),

    'stats' => [
        'active_members' => (int) env('ELITEFIT_STAT_MEMBERS', 247),
        'coaches' => (int) env('ELITEFIT_STAT_COACHES', 12),
        'hours' => env('ELITEFIT_STAT_HOURS', '6h–23h'),
        'surface_m2' => (int) env('ELITEFIT_STAT_SURFACE', 500),
    ],

];
