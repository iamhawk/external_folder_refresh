<?php

return [
    'routes' => [
        [
            'name' => 'scan#config',
            'url' => '/config',
            'verb' => 'GET',
        ],
        [
            'name' => 'scan#scan',
            'url' => '/scan',
            'verb' => 'POST',
        ],
        [
            'name' => 'settings#save',
            'url' => '/settings',
            'verb' => 'POST',
        ],
    ],
];
