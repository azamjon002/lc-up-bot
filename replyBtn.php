<?php
$request_contact = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([[['text' => "Kontaktni ulashish ⤴", "request_contact" => true]]], true, true);
$removeButton = new \TelegramBot\Api\Types\ReplyKeyboardRemove(true);

//$main_menu_btn = new \TelegramBot\Api\Types\ReplyKeyboardMarkup([
//    [['text' => 'Guruhlarim 👥'], ['text' => 'Profilim 👤']],[['text'=>"To'lovlarim 💵"], ['text'=>"SMSlar tarixi 📨"]]
//], false, true);

function getMainMenuBtn($chat_id) {
    $buttonsMain = [[]];
    $buttonsMain[0][] = ['text' => 'Guruhlarim 👥'];
    $buttonsMain[0][] = ['text' => 'Profilim 👤'];
    $buttonsMain[0][] = ['text'=>"To'lovlarim 💵"];
    $buttonsMain[0][] = ['text'=>"SMSlar tarixi 📨"];

    if ($chat_id){
        $student_id = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);
        $is_jarima_required = query("SELECT sozlamalars.jarima_ball_required, sozlamalars.summa_for_count FROM sozlamalars join centers ON centers.id = sozlamalars.center_id join filials ON filials.center_id = centers.id join students ON students.filial_id = filials.id WHERE students.id = '$student_id'")->fetch_assoc();
        if ($is_jarima_required['jarima_ball_required'] == 'ha'){
            $buttonsMain[0][] = ['text'=>"💵 Keyingi oy uchun to'lov hisobi 💵"];
        }
    }

    $buttonsMain = array_chunk($buttonsMain[0], 2);
    $main_menu_btn = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($buttonsMain, false, true);

    return $main_menu_btn;
}

