<?php

ini_set('display_errors',1);
require 'vendor/autoload.php';
require_once 'database.php';
require_once 'inlineBtn.php';
require_once 'replyBtn.php';


$botToken = '6503138059:AAHpMY-jnrVfr-q_01uouLwriGyNerUmKFA';
// https://api.telegram.org/bot5975116041:AAHecUKDBOSDuJuQvKCfS0aETpZ5Naqy0oI/setWebhook?url=https://419d-188-113-206-179.ngrok-free.app/lc-up/index.php

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new TelegramBot\Api\Client($botToken);

include 'command.php';

include 'callback.php';

include 'on.php';



 if(!empty($bot->getRawBody())){
 $bot->run();
 }
