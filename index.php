<?php

require 'vendor/autoload.php';
require_once 'database.php';
require_once 'inlineBtn.php';
require_once 'replyBtn.php';


$botToken = '5975116041:AAHecUKDBOSDuJuQvKCfS0aETpZ5Naqy0oI';
// https://api.telegram.org/bot5975116041:AAHecUKDBOSDuJuQvKCfS0aETpZ5Naqy0oI/setWebhook?url=https://f348-188-113-221-228.ngrok-free.app/lc-up/index.php

/**
 * @var $bot \TelegramBot\Api\Client | \TelegramBot\Api\BotApi
 */

$bot = new TelegramBot\Api\Client($botToken);

include 'command.php';

include 'callback.php';

include 'on.php';



$bot->run();