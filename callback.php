<?php


$bot->callbackQuery(static function(\TelegramBot\Api\Types\CallbackQuery $callbackQuery) use ($bot, $removeButton){

    $chat_id = $callbackQuery->getMessage()->getChat()->getId();
    $data = $callbackQuery->getData();
    $message_id = $callbackQuery->getMessage()->getMessageId();

    //AGAR TELEFON RAQAM BOYICHA USER 1 DAN KO'P BO'LSA VA ULARDAN BIRINI TANLASA
    if (strpos($data, 'userId') !== false){
        $user_id = explode('_',$data)[1];
        $mobile_number = explode('_',$data)[2];

        $role_hodim = query("SELECT * FROM role_user WHERE user_id = '$user_id'")->num_rows;

        if ($role_hodim == 0){
            $myfile = fopen("session/$chat_id.txt", "w+");
            fwrite($myfile, "user_id=" . $user_id . ";");
            fclose($myfile);
            $bot->deleteMessage($chat_id,$message_id);
            $bot->sendMessage($chat_id, "Muaffaqiyatli ✅ \nParolni kiriting");
        }else{
            $users_massiv = query("SELECT * FROM users where mobile_number = $mobile_number and deleted_at is null")->fetch_all();

            $button = [[]];
            foreach ($users_massiv as $result) {
                $center_id = query("SELECT center_id FROM filials where id = $result[16] and deleted_at is null")->fetch_assoc()['center_id'];
                $center_name = query("SELECT name FROM centers where id='$center_id'")->fetch_assoc()['name'];
                $button[0][] = ["text" => "$result[1] ➡️ $center_name", "callback_data" => "userId_$result[0]_$mobile_number"];
            }
            $button = array_chunk($button[0], 1);
            $users_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

            $bot->deleteMessage($chat_id,$message_id);
            $bot->sendMessage($chat_id, "Ushbu foydalanuvchi sifatida kirish taqiqlanadi ❌", "HTML", false, null,$users_btn);
        }

    }

    //LOGOUT
    if ($data == 'logout'){
        query("UPDATE users SET chat_id = null, status_for_bot = null WHERE chat_id = '$chat_id'");
        unlink("session/$chat_id.txt");

        $bot->sendMessage($chat_id, "Siz hisobdan chiqdingiz. Botdan qayta foydalanish uchun /start tugmasini bosing", null, false, null, $removeButton);
    }

    //PASSWORD EDIT
    if ($data == 'edit_password'){
        query("UPDATE users SET status_for_bot = 'eski password' WHERE chat_id = '$chat_id'");
        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"⬅️ Orqaga", 'callback_data'=>'parol_tahrirlashdan_ortga_qaytish']]]);
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id, 'Eski parolni kiriting', null, false, null, $removeButton);
        $bot->sendMessage($chat_id, "ℹ️ Parol yodingizdan ko'tarilgan bo'lsa o'quv markaz rahbariyatidan shaxsiy parolingizni o'zgaritirib berishini so'rang ℹ️", null, false, null, $btn);
    }

    //IF PASSWORD EDIT BACK
    if ($data == 'parol_tahrirlashdan_ortga_qaytish'){
        query("UPDATE users SET status_for_bot = 'login boldi' WHERE chat_id = '$chat_id'");
        $user_name_and_number = query("SELECT name,mobile_number,filial_id FROM users WHERE chat_id = '$chat_id'")->fetch_assoc();
        $filial_id = $user_name_and_number['filial_id'];
        $filial_nomi = query("SELECT name FROM filials where id = '$filial_id'")->fetch_assoc()['name'];
        $fish = $user_name_and_number['name'];
        $tel_number = $user_name_and_number['mobile_number'];


        $text = "👤 FISH: $fish\n📱 Telefon raqam: $tel_number\n🏪 O'quv markazi: $filial_nomi";

        $sozlamar_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Parolni o'zgartirish ✏️", 'callback_data'=>'edit_password']],
            [['text'=>'Hisobdan chiqish ➡️', 'callback_data'=>'logout']]]);
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id, $text, 'HTML', false, null, $sozlamar_btn);
    }

    if ($data == 'eski_parolga_qaytarish'){
        query("UPDATE users SET status_for_bot = 'eski password' WHERE chat_id = '$chat_id'");
        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"⬅️ Orqaga", 'callback_data'=>'parol_tahrirlashdan_ortga_qaytish']]]);
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id, 'Eski parolni kiriting', null, false, null, $removeButton);
        $bot->sendMessage($chat_id, "ℹ️ Parol yodingizdan ko'tarilgan bo'lsa o'quv markaz rahbariyatiga shaxsiy parolingizni o'zgaritirib berishini so'rang ℹ️", null, false, null, $btn);
    }

    //GURUHGA KIRISH
    if (strpos($data, 'group')!== false){
        $group_id = explode('_', $data)[1];

        $bot->deleteMessage($chat_id,$message_id);
        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"ℹ️ Ma'lumotlar", 'callback_data'=>"info_$group_id"],['text'=>"Davomat & Baholar", 'callback_data'=>"progolAnMark_$group_id"]]]);
        $bot->sendMessage($chat_id, "Kerakli bo'limlardan birini tanlang", null, false, null, $btn);
    }

    //GURUH MALUMOTLARI
    if (strpos($data, 'info')!== false){
        $group_id = explode('_', $data)[1];
        $user_id = query("SELECT id FROM users WHERE chat_id = '$chat_id'")->fetch_assoc()['id'];
        $student_id = query("SELECT s.id FROM students s join users u ON s.user_id = u.id WHERE u.id = '$user_id'")->fetch_assoc()['id'];

        $group = query("SELECT * FROM `groups` WHERE `id`='$group_id'")->fetch_assoc();
        $group_name = $group['name'];
        $cost = number_format($group['cost']);
        $room_id = $group['room_id'];
        $course_id = $group['fan_id'];
        $start = $group['start'];
        $finish = $group['finish'];
        $room = query("SELECT name FROM `rooms` where `id` = '$room_id'")->fetch_assoc()['name'];
        $course = query("SELECT name FROM `fans` where `id` = '$course_id'")->fetch_assoc()['name'];

        $teachers_massiv = query("SELECT w.name FROM workers w join group_worker gw ON w.id=gw.worker_id where gw.group_id='$group_id'")->fetch_all();

        $teachers_name = '';
        foreach ($teachers_massiv as $key => $item) {
            if ($key == array_key_last($teachers_massiv)){
                $teachers_name .=  implode(',', $item);
            }else{
                $teachers_name .=  implode(',', $item) .", ";
            }
        }


        $all_weeks =  query("SELECT w.name FROM weeks w join group_week gw ON w.id = gw.week_id where gw.group_id='$group_id'")->fetch_all();;
        $weeks_str = '';
        foreach ($all_weeks as $key => $item) {
            if ($key == array_key_last($all_weeks)){
                $weeks_str .= implode(',', $item);
            }else{
                $weeks_str .= implode(',', $item) .", ";
            }
        }


        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"⬅️ Orqaga", 'callback_data'=>"group_$group_id"]]]);
        $text = "👥 Guruh nomi: $group_name\n💵 Narxi:$cost\n📚 Kurs nomi: $course\n🏘 Xona: $room\n👩‍🏫 O'qituvchi: $teachers_name\n📅 Dars kunlari: $weeks_str\n🔓 Boshlanish vaqti: $start\n🔐 Tugash vaqti: $finish";
        $bot->deleteMessage($chat_id,$message_id);
        $bot->sendMessage($chat_id, $text, null, false, null, $btn);
    }

    //DAVOMAT SHU OY UCHUN
    if (strpos($data, 'progolAnMark') !== false){
        $group_id = explode('_', $data)[1];
        $user_id = query("SELECT id FROM users WHERE chat_id = '$chat_id'")->fetch_assoc()['id'];
        $student_id = query("SELECT s.id FROM students s join users u ON s.user_id = u.id WHERE u.id = '$user_id'")->fetch_assoc()['id'];

        $progol_system = query("SELECT * FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id' ORDER BY `day` DESC LIMIT 10")->fetch_all();


        $progols = "So'ngi 10 kunlik davomat\n\nSana           Davomat     Baho\n\n";

        if (empty($progol_system)){
            $progols .= "❌ Ushbu oyda ma'lumot mavjud emas ❌";
        }else{

            foreach ($progol_system as $key => $item) {
                $baho = $item['7'] ? "  ".$item['7'] : '➖';
                $day = date('d-M',  strtotime($item['1']));

                if ($item['8'] == '0'){
                    $progols .= $day."             ❌           ".$baho."\n\n";
                }else{
                    $progols .= $day."             ✅           ".$baho."\n\n";
                }
            }

        }

        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Oylar bo'yicha tanlash", 'callback_data'=>"barchaOylar_$group_id"."_"."$student_id"]],[['text'=>"⬅️ Orqaga", 'callback_data'=>"group_$group_id"]]]);
        $bot->deleteMessage($chat_id,$message_id);
        $bot->sendMessage($chat_id, $progols, null,false,null,$btn);

    }

    //OYLARDAN BIRINI TANLASH
    if (strpos($data, 'barchaOylar')!== false){
        $group_id = explode('_', $data)[1];
        $student_id = explode('_', $data)[2];


        $oylar = query("SELECT month(day) FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id' GROUP BY month(day) ORDER BY month(day) ASC")->fetch_all();

        $button = [[]];
        $months_massiv = query("SELECT name, id FROM `months`")->fetch_all();


        foreach ($oylar as $result) {
            $id = $result[0];
            $oy_name = query("SELECT name, id FROM `months` WHERE id = '$id'")->fetch_assoc()['name'];
            $button[0][] = ["text" => "🏪 $oy_name", "callback_data" => "checkedMonth_$group_id".'_'."$student_id".'_'."$id"];
        }

        $button = array_chunk($button[0], 2);
        $button[array_key_last($button)+1][] = ["text" => '⬅️ Orqaga', "callback_data" => "progolAnMark_$group_id"];

        $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id, "Oylardan birini tanlang", null, false, null, $b);
    }

    //TANLANGAN OY UCHUN PROGOL SISTEMANI KO'RISH
    if (strpos($data, 'checkedMonth')!== false){
        $group_id = explode('_', $data)[1];
        $student_id = explode('_', $data)[2];
        $month_id = explode('_', $data)[3];

        $progol_system = query("SELECT * FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id' AND MONTH(`day`) = '$month_id' AND YEAR(day) = YEAR(CURDATE()) ORDER BY `day` ASC")->fetch_all();

        $oy_name = query("SELECT name FROM `months` WHERE id = '$month_id'")->fetch_assoc()['name'];
        $progols = "$oy_name oyi uchun davomat:\n\nSana     Davomat   Baho\n\n";

        if (empty($progol_system)){
            $progols .= "❌ Sizda progol sistema mavjud emas ❌";
        }else{

            foreach ($progol_system as $key => $item) {
                $baho = $item['7'] ? "  ".$item['7'] : '➖';
                $day = date('d',  strtotime($item['1']));

                if ($item['8'] == '0'){
                    $progols .= $day."              ❌           ".$baho."\n\n";
                }else{
                    $progols .= $day."              ✅           ".$baho."\n\n";
                }
            }
        }

        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"⬅️ Orqaga", 'callback_data'=>"barchaOylar_$group_id"."_"."$student_id"]]]);
        $bot->deleteMessage($chat_id,$message_id);
        $bot->sendMessage($chat_id, $progols, null, false,  null, $btn);
    }
});

