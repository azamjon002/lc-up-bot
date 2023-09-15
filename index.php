<?php

ini_set('display_errors',1);
require 'vendor/autoload.php';
require_once 'database.php';
require_once 'inlineBtn.php';
require_once 'replyBtn.php';
require_once 'config.php';

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new TelegramBot\Api\Client($botToken);

include 'command.php';

include 'callback.php';

include 'on.php';

<<<<<<< HEAD


 if(!empty($bot->getRawBody())){
 $bot->run();
 }
=======
$bot->run();
>>>>>>> 62c426693d48485b8977d386d5e29ef39f20b757
