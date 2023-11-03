<?php


$bot->callbackQuery(static function(\TelegramBot\Api\Types\CallbackQuery $callbackQuery) use ($bot, $removeButton){

    $chat_id = $callbackQuery->getMessage()->getChat()->getId();
    $data = $callbackQuery->getData();
    $message_id = $callbackQuery->getMessage()->getMessageId();
    $main_menu_btn = getMainMenuBtn($chat_id);

    //AGAR TELEFON RAQAM BOYICHA USER 1 DAN KO'P BO'LSA VA ULARDAN BIRINI TANLASA
    if (strpos($data, 'studentId') !== false){
        $student_id = explode('_',$data)[1];
        $myfile = fopen("session/$chat_id.txt", "w+");
        fwrite($myfile, "student_id=" . $student_id . ";");
        fclose($myfile);
        $bot->deleteMessage($chat_id,$message_id);
        $back = backBtn('mobile_number_tasdiqlashga_qaytish');
        $bot->sendMessage($chat_id, "Muaffaqiyatli ✅ \nParolni kiriting", null, false, null, $back);
    }

    //mobile_number_tasdiqlashga_qaytish
    if ($data == 'mobile_number_tasdiqlashga_qaytish'){
        unlink("session/$chat_id.txt");
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id,"Telefon raqamni kiriting\nNa'muna: 998991234455", 'HTML',false,null, $request_contact);
    }

    //LOGOUT
    if (strpos($data, 'logout') !== false){
        $userId = explode('_', $data)[1];
        $chatIds = query("SELECT chat_ids_bot, JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS FROM users WHERE users.chat_ids_bot LIKE '%$chat_id%'")->fetch_assoc()['chat_ids_bot'];

        $jsonMassiv = json_decode($chatIds);

        if (count($jsonMassiv->data) > 1){

            foreach ($jsonMassiv->data as $key => $value) {
                if ($value->chat_id == $chat_id){
                    unset($jsonMassiv->data[$key]);
                }
            }

            $str = '';
            foreach ($jsonMassiv->data as $key => $item) {
                if ($key == array_key_last($jsonMassiv->data)){
                    $str .= '{"status": "'.$item->status.'", "chat_id" : "'.$item->chat_id.'"}';
                }else{
                    $str .= '{"status": "'.$item->status.'", "chat_id" : "'.$item->chat_id.'"},';
                }
            }

            $string = '{"data" : ['.$str.']}';

            query("update users set `chat_ids_bot` = '$string' where id = $userId");
        }else{
            query("UPDATE users SET chat_ids_bot = null where id = '$userId'");
        }
        unlink("session/$chat_id.txt");
        $bot->sendMessage($chat_id, "Siz hisobdan chiqdingiz. Botdan qayta foydalanish uchun /start tugmasini bosing", null, false, null, $removeButton);
    }

    //PASSWORD EDIT
    if ($data == 'edit_password'){
        updateStatus($chat_id, 'eski password');
        $btn = backBtn('parol_tahrirlashdan_ortga_qaytish');
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id, 'Eski parolni kiriting', null, false, null, $removeButton);
        $bot->sendMessage($chat_id, "ℹ️ Parol yodingizdan ko'tarilgan bo'lsa o'quv markaz rahbariyatidan shaxsiy parolingizni o'zgaritirib berishini so'rang ℹ️", null, false, null, $btn);
    }

    //IF PASSWORD EDIT BACK
    if ($data == 'parol_tahrirlashdan_ortga_qaytish'){
        updateStatus($chat_id, 'login boldi');
        $user_name_and_number = query("SELECT name,mobile_number,filial_id, JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS FROM users WHERE users.chat_ids_bot LIKE '%$chat_id%'")->fetch_assoc();
        $filial_id = $user_name_and_number['filial_id'];
        $filial_nomi = query("SELECT name FROM filials where id = '$filial_id'")->fetch_assoc()['name'];
        $fish = $user_name_and_number['name'];
        $tel_number = $user_name_and_number['mobile_number'];
        $userId = $user_name_and_number['id'];


        $text = "👤 FISH: $fish\n📱 Telefon raqam: $tel_number\n🏪 O'quv markazi: $filial_nomi";

        $sozlamar_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Parolni o'zgartirish ✏️", 'callback_data'=>'edit_password']],
            [['text'=>'Hisobdan chiqish ➡️', 'callback_data'=>"logout_$userId"]]]);
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id,"Sizning shaxsiy ma'lumotlaringiz", null, false, null, $main_menu_btn);
        $bot->sendMessage($chat_id, $text, 'HTML', false, null, $sozlamar_btn);
    }

    if ($data == 'eski_parolga_qaytarish'){
        updateStatus($chat_id, 'eski password');

        $btn = backBtn('parol_tahrirlashdan_ortga_qaytish');
        $bot->deleteMessage($chat_id, $message_id);
        $bot->sendMessage($chat_id, 'Eski parolni kiriting', null, false, null, $removeButton);
        $bot->sendMessage($chat_id, "ℹ️ Parol yodingizdan ko'tarilgan bo'lsa o'quv markaz rahbariyatiga shaxsiy parolingizni o'zgaritirib berishini so'rang ℹ️", null, false, null, $btn);
    }

    //GURUHGA KIRISH
    if (strpos($data, 'group')!== false){
        $group_id = explode('_', $data)[1];
        $student_id = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

        $is_jarima_required = query("SELECT sozlamalars.jarima_ball_required, sozlamalars.summa_for_count FROM sozlamalars join centers ON centers.id = sozlamalars.center_id join filials ON filials.center_id = centers.id join students ON students.filial_id = filials.id WHERE students.id = '$student_id'")->fetch_assoc();
        $bot->deleteMessage($chat_id,$message_id);

        $buttons = [[]];
        $buttons[0][] = ['text'=>"ℹ️ Ma'lumotlar", 'callback_data'=>"info_$group_id"];
        $buttons[0][] = ['text'=>"☑️ Davomat & Baholar", 'callback_data'=>"progolAnMark_$group_id"];

        if ($is_jarima_required['jarima_ball_required'] == 'ha'){
            $summa = $is_jarima_required['summa_for_count'];
            $buttons[0][] = ['text'=>"📛 Jarimalar & Rag'batlantirishlar 🌟", 'callback_data'=>"jarima_$group_id"."_".$student_id."_".$summa];
        }

        $buttons = array_chunk($buttons[0], 2);
        $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($buttons);

        $bot->sendMessage($chat_id, "Kerakli bo'limlardan birini tanlang", null, false, null, $b);
    }

    //GURUH MALUMOTLARI
    if (strpos($data, 'info')!== false){
        $group_id = explode('_', $data)[1];
        $student_id = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

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

        $teachers_name = createSqlIn($teachers_massiv);

        $all_weeks =  query("SELECT w.name FROM weeks w join group_week gw ON w.id = gw.week_id where gw.group_id='$group_id'")->fetch_all();;
        $weeks_str = createSqlIn($all_weeks);

        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"⬅️ Orqaga", 'callback_data'=>"group_$group_id"]]]);
        $text = "👥 Guruh nomi: $group_name\n💵 Narxi:$cost\n📚 Kurs nomi: $course\n🏘 Xona: $room\n👩‍🏫 O'qituvchi: $teachers_name\n📅 Dars kunlari: $weeks_str\n🔓 Boshlanish vaqti: $start\n🔐 Tugash vaqti: $finish";
        $bot->deleteMessage($chat_id,$message_id);
        $bot->sendMessage($chat_id, $text, null, false, null, $btn);
    }


    //DAVOMAT SHU OY UCHUN
    if (strpos($data, 'progolAnMark') !== false){
        $group_id = explode('_', $data)[1];
        $student_id = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

        $progol_system = query("SELECT * FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id' ORDER BY `day` DESC LIMIT 10")->fetch_all();


        $progols = "So'ngi 10 kunlik davomat\n\nSana           Davomat     Baho\n\n";

        if (empty($progol_system)){
            $progols = "❌ Ushbu oyda ma'lumot mavjud emas ❌";
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

        if (empty($oylar)){
            $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[["text" => '⬅️ Orqaga', "callback_data" => "progolAnMark_$group_id"]]]);
            $bot->deleteMessage($chat_id, $message_id);
            $bot->sendMessage($chat_id, "❌ Sizda hech bir oyda ma'lumot mavjud emas", null, false, null, $btn);
        }else{
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

    //JARIMALARNI KORISH
    if (strpos($data, 'jarima')!== false){
        $group_id = explode('_', $data)[1];;
        $student_id = explode('_', $data)[2];;
        $summa = explode('_', $data)[3];;

        $balls = query("SELECT count, day FROM balls WHERE student_id = '$student_id' and group_id = '$group_id' and MONTH(day) = MONTH(now()) and YEAR(day) = YEAR(now())")->fetch_all();



        $jarimas_str = "Sizning jarimalaringiz:🙅‍♂️🙅‍♂️🙅‍♂️\n";
        $jarima_count = 0;
        $ragbat_str = "Sizning rag'batlaringiz:🙅‍♂️🙅‍♂️🙅‍♂️\n";
        $ragbat_count = 0;

        foreach ($balls as $ball) {
            if ($ball[0]<0){
                $jarimas_str = str_replace('🙅‍♂️🙅‍♂️🙅‍♂️','', $jarimas_str);
                $jarimas_str.=$ball[1].': '.$ball[0].PHP_EOL;
                $jarima_count-=$ball[0];
            }else{
                $ragbat_str = str_replace('🙅‍♂️🙅‍♂️🙅‍♂️','', $ragbat_str);
                $ragbat_str.=$ball[1].': '.$ball[0].PHP_EOL;
                $ragbat_count+=$ball[0];
            }
        }

        $umumiy = (-$jarima_count+$ragbat_count)*intval($summa);
        if ($umumiy>0){
            $hisobKitob = "😊 Sizda keyingi oy uchun rag'bat: ".number_format($umumiy)."😊";
        }else{
            $hisobKitob = "☹️ Sizda keyingi oy uchun jarima: ".number_format($umumiy)."☹️";
        }

        $bot->sendMessage($chat_id, $ragbat_str);
        $bot->sendMessage($chat_id, $jarimas_str);
        $bot->sendMessage($chat_id,$hisobKitob);
    }
});
