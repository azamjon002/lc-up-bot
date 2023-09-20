<?php

require 'vendor/autoload.php';
require_once 'database.php';
require_once 'inlineBtn.php';
require_once 'replyBtn.php';
require_once 'config.php';
require_once 'function.php';

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new TelegramBot\Api\Client($botToken);

include 'command.php';

include 'callback.php';

include 'on.php';
try {

    $bot->run();
}catch (Exception $exception){

}

try {
    $bot->run();
}catch (Exception $exception){

}
