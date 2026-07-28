<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Slider de noticias más vistas
    |--------------------------------------------------------------------------
    |
    | Esta configuración será reemplazable por los valores del dashboard.
    | Mientras se implementa el panel, puede ajustarse desde el archivo .env.
    |
    */
    'most_viewed_slider' => [
        'mode' => env('MOST_VIEWED_SLIDER_MODE', 'automatic'),
        'interval' => (int) env('MOST_VIEWED_SLIDER_INTERVAL', 6000),
        'loop' => env('MOST_VIEWED_SLIDER_LOOP', true),
    ],
];
