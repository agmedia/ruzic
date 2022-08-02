<?php
// AGmedia Custom
define('OC_ENV', [
    'env'                         => 'local',
    //
    'free_shipping_amount'        => 300,
    'default_shipping_price'      => 35,
    'shipping_collector_price'    => 35,
    'shipping_collector_defaults' => [
        0 => [
            'time'  => '9-16h',
            'max'   => '36',
            'price' => 35
        ]
    ],
    'shipping_collector_regions' => [
        0 => [
            'id'  => 848,
            'label' => 'istok',
            'code'   => 'ZGI'
        ],
        1 => [
            'id'  => 867,
            'label' => 'zapad',
            'code'   => 'ZGZ'
        ]
    ],
    'delivery_region' => [
        0 => [
            'id'  => 225,
            'label' => 'zagreb'
        ],
        1 => [
            'id'  => 226,
            'label' => 'croatia'
        ]
    ],
    'delivery_zagreb' => 225,
    'delivery_croatia' => 226,
    'service'                     => [],
]);