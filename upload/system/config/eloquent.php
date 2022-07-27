<?php

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    "driver"    => "mysql",
    "host"      => DB_HOSTNAME,
    "database"  => DB_DATABASE,
    "username"  => DB_USERNAME,
    "password"  => DB_PASSWORD,
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci',
    'prefix'    => 'oc_',
]);

$capsule->bootEloquent();