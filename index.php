<?php

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



$bot->run();