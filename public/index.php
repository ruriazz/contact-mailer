<?php

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "app.php";

$app = new App();

$data = $app->post('name, email, message, token');
if(!$data->name || !$data->email || !$data->message || !$data->token) {
    //errror
    $app->error("unknown request");
}

if(!$app->tokenValid($data->token)) {
    $app->error("invalid submit token!");
}

$sent = $app->sendMessage($data->email, $data->name, $data->message);

if($sent) {
    $app->success("message has been sent");
}

$app->error("message failed to send");
