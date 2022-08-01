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
    'service'                     => [],
]);