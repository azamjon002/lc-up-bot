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
        $bot->sendMessage($chat_id, 'Eski parolni kiriting', null, false, null, $removeButton);
    }

    //GURUHGA KIRISH
    if (strpos($data, 'group')!== false){
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

        $text = "👥 Guruh nomi: $group_name\n💵 Narxi:$cost\n📚 Kurs nomi: $course\n🏘 Xona: $room\n👩‍🏫 O'qituvchi: $teachers_name\n📅 Dars kunlari: $weeks_str\n🔓 Boshlanish vaqti: $start\n🔐 Tugash vaqti: $finish";

        $progol_system = query("SELECT * FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id' AND MONTH(day) = MONTH(now())")->fetch_all();

        $progols = '';
        $oy = date('F');

        if (empty($progol_system)){
            $progols .= "❌ Ushbu oyda ma'lumot mavjud emas ❌";
        }else{

            foreach ($progol_system as $key => $item) {

                $baho = $item['7'] ?? '➖';
                $day = "Sana: ".date('d',  strtotime($item['1']));


                if ($key == array_key_last($progol_system)){
                    if ($item['8'] == '1'){
                        $progols .= $day." Davomat: ❌ Baho: ".$baho."\n";
                    }else{
                        $progols .= $day." Davomat: ✅ Baho: ".$baho."\n";
                    }
                }else{
                    if ($item['8'] == '1'){
                        $progols .= $day." Davomat: ❌ Baho: ".$baho."\n";
                    }else{
                        $progols .= $day." Davomat: ✅ Baho: ".$baho."\n";
                    }
                }
            }
        }


        $btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Barchasini ko'rish", 'callback_data'=>"allPragol_$group_id"."_"."$student_id"]]]);
        $bot->sendMessage($chat_id, $text);
        $bot->sendMessage($chat_id, $oy." oyi uchun\n".$progols, null,false,null,$btn);

    }

    //BARCHA PROGOL SISTEMANI KO'RISH
    if (strpos($data, 'allPragol')!== false){
        $group_id = explode('_', $data)[1];
        $student_id = explode('_', $data)[2];

        $progol_system = query("SELECT * FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id'")->fetch_all();

        var_dump("SELECT * FROM `progol_systems` WHERE `group_id`='$group_id' AND `student_id` = '$student_id'");
        $progols = '';

        if (empty($progol_system)){
            $progols .= "❌ Sizda progol sistema mavjud emas ❌";
        }else{

            foreach ($progol_system as $key => $item) {

                $baho = $item['7'] ?? '➖';
                $day = "Sana: ".date('d-M',  strtotime($item['1']));


                if ($key == array_key_last($progol_system)){
                    if ($item['8'] == '1'){
                        $progols .= $day." Davomat: ❌ Baho: ".$baho."\n";
                    }else{
                        $progols .= $day." Davomat: ✅ Baho: ".$baho."\n";
                    }
                }else{
                    if ($item['8'] == '1'){
                        $progols .= $day." Davomat: ❌ Baho: ".$baho."\n";
                    }else{
                        $progols .= $day." Davomat: ✅ Baho: ".$baho."\n";
                    }
                }
            }
        }


        $bot->sendMessage($chat_id, $progols);
    }
});

