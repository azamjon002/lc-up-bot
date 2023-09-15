<?php

$bot->command('start', function (\TelegramBot\Api\Types\Message $message)
use ($bot, $request_contact, $main_menu_btn)

{
    $chat_id = $message->getChat()->getId();
    $first_name = $message->getChat()->getFirstName();

    $has_login = query("SELECT * FROM users WHERE chat_id = '$chat_id'")->num_rows;

    if ($has_login > 0){
        query("UPDATE users SET status_for_bot = 'login boldi' WHERE chat_id = '$chat_id'");
         $bot->sendMessage($chat_id, "Siz botdan oldin ro'yhatdan o'tgansiz!",'HTML');
         $bot->sendMessage($chat_id, "O'zingizga kerakli menyulardan birini tanlang!",'HTML', false, null,  $main_menu_btn);
    }else{
        $bot->sendMessage($chat_id,"Assalomu alaykum $first_name. Ro'yhatdan o'tgan parolingiz orqali o'z kabinetingizga kiring", 'HTML');
        $bot->sendMessage($chat_id,"Telefon raqamni kiriting\nNa'muna: 998991234455", 'HTML',false,null, $request_contact);
    }
});
