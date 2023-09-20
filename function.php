<?php

function insertChatIds($chat_id, $user_id, $hash_writeable_chatId =false){
    if ($hash_writeable_chatId){
        $jsonDecode=  json_decode($hash_writeable_chatId);
        $jsonStr = '{ "status": "login boldi", "chat_id": "'.$chat_id.'" }';
        $jsonDecode->data[] = json_decode($jsonStr);
        $string = json_encode($jsonDecode);
        query("update users set `chat_ids_bot` = '$string' where id = $user_id");
    }else{
        $json  = '{ "data": [ { "status": "login boldi", "chat_id": "'.$chat_id.'" }]}';
        query("update users set `chat_ids_bot` = '$json' where id = $user_id");
    }
}
function getStatus($chat_id){
    $has_login = query("SELECT JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS FROM users WHERE users.chat_ids_bot->>'$.data[*].chat_id' LIKE '%$chat_id%'")->fetch_assoc()['STATUS'];

    $status_array = json_decode($has_login);
    $userStatus = '';
    foreach ($status_array as $item) {
        if ($item->chat_id == $chat_id){
            $userStatus .= $item->status;
        }
    }

    return $userStatus;
}

function getChatId($chat_id){
    $has_login = query("SELECT JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS FROM users WHERE users.chat_ids_bot->>'$.data[*].chat_id' LIKE '%$chat_id%'")->fetch_assoc()['STATUS'];

    $status_array = json_decode($has_login);
    $userStatus = '';
    foreach ($status_array as $item) {
        if ($item->chat_id == $chat_id){
            $userStatus .= $item->status;
        }
    }

    return $userStatus;
}

function updateStatus($chat_id, $status){
    $has_login = query("SELECT JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS FROM users WHERE users.chat_ids_bot->>'$.data[*].chat_id' LIKE '%$chat_id%'")->fetch_assoc()['STATUS'];
    $jsonArray = json_decode($has_login);

    foreach ($jsonArray as $key=> $item) {
        if ($item->chat_id == $chat_id){
            query("update users set chat_ids_bot = JSON_SET(chat_ids_bot, '$.data[$key].status', '$status')");
        }
    }

    return true;
}



function createSqlIn($massiv){
    $in = '';
    foreach ($massiv as $key => $item) {
        if ($key == array_key_last($massiv)){
            $in .= "'".$item['0']."'";
        }else{
            $in .= "'" .$item['0']."',";
        }
    }

    return $in;
}

//function inlineButtonMaker($massiv, $model, $callback_data, $length){
//    $button = [[]];
//    foreach ($massiv as $result) {
//        $filial_name = query("SELECT name FROM $model where id = $result[1] and deleted_at is null")->fetch_assoc()['name'];
//        $button[0][] = ["text" => "$result[0] ➡️ $filial_name", "callback_data" => "$callback_data".'_'."$result[2]"];
//    }
//    $button = array_chunk($button[0], $length);
//    $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);
//
//    return $btn;
//}

function backBtn($callback_data){
    $btn=  new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"⬅️ Orqaga", 'callback_data'=>$callback_data]]]);
    return $btn;
}