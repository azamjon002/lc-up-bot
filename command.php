<?php

$bot->command('start', function (\TelegramBot\Api\Types\Message $message)
use ($bot, $request_contact)

{
    $chat_id = $message->getChat()->getId();
    $first_name = $message->getChat()->getFirstName();

    $has_login = getStatus($chat_id);

    $main_menu_btn = getMainMenuBtn($chat_id);

    if ($has_login != ''){
        updateStatus($chat_id, 'login boldi');
        $bot->sendMessage($chat_id, "Siz botdan oldin ro'yhatdan o'tgansiz!",'HTML');
        $bot->sendMessage($chat_id, "O'zingizga kerakli menyulardan birini tanlang!",'HTML', false, null,  $main_menu_btn);
    }else{
        unlink("session/$chat_id.txt");
        $bot->sendMessage($chat_id,"Assalomu alaykum $first_name. Ro'yhatdan o'tgan parolingiz orqali o'z kabinetingizga kiring", 'HTML');
        $bot->sendMessage($chat_id,"Telefon raqamni kiriting\nNa'muna: 998991234455", 'HTML',false,null, $request_contact);
    }
});
