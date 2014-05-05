<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/manager.php';

$app = new Silex\Application();
$app['debug'] = false;

$app->get('/', function() use($app) {
    $rand_user_id = rand(100, 200);
    return '<a href="user/'.$rand_user_id.'">Random user #'.$rand_user_id.'</a>';
});

$app->get('/user/{user_id}', function($user_id) use($app) {
    if (file_exists(__DIR__ . '/processing.' . $user_id)) {
        // HTTP NO CONTENT
        return $app->json(['status' => 'processing'], 204);
    }

    if (file_exists(__DIR__ . '/cache.' . $user_id)) {
        $raw_data = file_get_contents(__DIR__ . '/cache.' . $user_id);
        $data = unserialize($raw_data);
        $created = DateTime::createFromFormat('U', $data['creation_datetime'])->format(DateTime::W3C);
        $finished = DateTime::createFromFormat('U', $data['ready_datetime'])->format(DateTime::W3C);

        // HTTP OK
        return $app->json([
            'created' => $created,
            'finished' => $finished
        ]);
    }
    else {
        $created = create_task($user_id);
        
        // HTTP ACCEPTED
        return $app->json([
            'created' => $created,
        ], 202);
    }
});

$app->run(); 
