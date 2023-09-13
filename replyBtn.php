<?php
$request_contact = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Kontaktni ulashish ⤴", "request_contact" => true]]], true, true);
$removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);

$main_menu_btn = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([
    [['text' => 'Guruhlarim 👥'], ['text' => 'Profilim 👤']]
], false, true);