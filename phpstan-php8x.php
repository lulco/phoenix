<?php

$config = [];
if (PHP_VERSION_ID >= 80000) {
    $config['parameters']['ignoreErrors'][] = '#no value type specified in iterable type PDOStatement#';
}

return $config;
