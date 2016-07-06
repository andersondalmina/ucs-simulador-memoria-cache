<?php
return [
    'settings' => [
        'displayErrorDetails' => false, // set to false in production

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],
    ],

    'errorHandler' => function(){
        echo('<H1>Um Erro Ocorreu!</h1>');
        exit();
    }
];
