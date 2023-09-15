<?php

$bot->on(static function (){}, static function(\TelegramBot\Api\Types\Update $update) use ($bot, $main_menu_btn){
    $textChecker = $update->getMessage()->getText();
    $chat_id = $update->getMessage()->getChat()->getId();
    $user_status = query("SELECT status_for_bot FROM users WHERE chat_id= '$chat_id'")->fetch_assoc()['status_for_bot'];


    //LOGIN
    if (!$user_status){
        if (!file_exists("session/$chat_id.txt")){
            if ($textChecker) {
                $telefon_raqam = $textChecker;
            }else {
                $number = $update->getMessage()->getContact()->getPhoneNumber();
                $telefon_raqam = str_replace('+', '', $number);
            }

            $is_number = preg_match("/^[0-9]+$/", $telefon_raqam);
            if ($is_number && strlen($telefon_raqam) == 12){

                $bazada_bor = query("SELECT mobile_number FROM users where mobile_number = $telefon_raqam and deleted_at is null")->num_rows;

                if ($bazada_bor == 1 ){
                    $user_id = query("SELECT id FROM users where mobile_number = $telefon_raqam")->fetch_assoc()['id'];
                    $myfile = fopen("session/$chat_id.txt", "w+");
                    fwrite($myfile, "user_id=" . $user_id . ";");
                    fclose($myfile);
                    $bot->sendMessage($chat_id, "Muaffaqiyatli ✅ \nParolni kiriting");
                }

                elseif ($bazada_bor == 0){
                    $bot->sendMessage($chat_id, "Ushbu raqam dasturda mavjud emas ❌");
                }

                else{
                    $users_massiv = query("SELECT * FROM users where mobile_number = $telefon_raqam and deleted_at is null")->fetch_all();

                    $button = [[]];
                    foreach ($users_massiv as $result) {
                        $center_id = query("SELECT center_id FROM filials where id = $result[16] and deleted_at is null")->fetch_assoc()['center_id'];
                        $center_name = query("SELECT name FROM centers where id='$center_id'")->fetch_assoc()['name'];
                        $button[0][] = ["text" => "$result[1] ➡️ $center_name", "callback_data" => "userId_$result[0]_$telefon_raqam"];
                    }
                    $button = array_chunk($button[0], 1);
                    $users_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

                    $bot->sendMessage($chat_id, "Ushbu raqamdan bir nechta foydalanuvchi ro'yhatdan o'tgan. Siz kimsiz ?", "HTML", false, null,$users_btn);
                }
            }else{
                $bot->sendMessage($chat_id, "Telefon raqam xato kiritildi ❌ Na'munaga qarang");
            }

        }else{
            // PAROL KELSA
            $hasSessionUserId = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);
            $hash_password = query("SELECT password FROM users where id = $hasSessionUserId")->fetch_assoc()['password'];

            if (password_verify($textChecker, $hash_password)){
                query("UPDATE users SET chat_id = '$chat_id', status_for_bot = 'login boldi' WHERE id ='$hasSessionUserId'");

                $bot->sendMessage($chat_id, "Hush kelibsiz!",'HTML');
                $bot->sendMessage($chat_id, "O'zingizga kerakli menyulardan birini tanlang!",'HTML', false, null,  $main_menu_btn);
            }else{
                $bot->sendMessage($chat_id, 'Parol xato kiritildi 🤷‍♂️. Iltimos qaytadan harakat qiling');
            };
        }
    }


    //PROFILIM
    if ($textChecker == 'Profilim 👤' && $user_status == 'login boldi'){

        $user_name_and_number = query("SELECT name,mobile_number,filial_id FROM users WHERE chat_id = '$chat_id'")->fetch_assoc();
        $filial_id = $user_name_and_number['filial_id'];
        $filial_nomi = query("SELECT name FROM filials where id = '$filial_id'")->fetch_assoc()['name'];
        $fish = $user_name_and_number['name'];
        $tel_number = $user_name_and_number['mobile_number'];


        $text = "👤 FISH: $fish\n📱 Telefon raqam: $tel_number\n🏪 O'quv markazi: $filial_nomi";

        $sozlamar_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Parolni o'zgartirish ✏️", 'callback_data'=>'edit_password']],
            [['text'=>'Chiqish ➡️', 'callback_data'=>'logout']]]);

        $bot->sendMessage($chat_id, $text, 'HTML', false, null, $sozlamar_btn);
    };

    //PAROLNI TAHRIRLASH
    if ($user_status == 'eski password'){
        $user_hash_password = query("SELECT password FROM users WHERE chat_id = '$chat_id'")->fetch_assoc()['password'];
        if (password_verify($textChecker, $user_hash_password)){
            query("UPDATE users SET status_for_bot = 'yangi password' WHERE chat_id = '$chat_id'");
            $bot->sendMessage($chat_id, "Yangi parolni kiriting");
        }else{
            $bot->sendMessage($chat_id, "❌ Parol xato kiritildi ❌");
        }
    }

    if ($user_status == 'yangi password'){
        if (strlen($textChecker) >= 4 ){
            query("UPDATE users SET status_for_bot = 'login boldi' WHERE chat_id = '$chat_id'");
            $new_hash_password = password_hash($textChecker, PASSWORD_BCRYPT);
            query("UPDATE users SET password = '$new_hash_password' WHERE chat_id = '$chat_id'");
            $bot->sendMessage($chat_id, '✅ Parol muaffaqiyatli tahrirlandi ✅', null, false, null, $main_menu_btn);
        }else{
            $bot->sendMessage($chat_id, "⭕️ Parol 4 ta ishoradan kam bo'lmasligi kerak");
        }

    }

    //GURUHLAR
    if ($textChecker == "Guruhlarim 👥" &&  $user_status == 'login boldi'){
        $user_id = query("SELECT id FROM `users` WHERE `chat_id` = '$chat_id'")->fetch_assoc()['id'];

        $s = query("SELECT students.id FROM students JOIN users ON users.id = students.user_id WHERE users.id    = '$user_id'")->fetch_assoc()['id'];
        $g = query("SELECT group_id FROM group_student WHERE student_id = '$s'")->fetch_all();

        $in = '';
        foreach ($g as $key => $item) {
            if ($key == array_key_last($g)){
                $in .= "'" . implode(',', $item) ."'";
            }else{
                $in .= "'" . implode(',', $item) ."',";
            }
        }

        $groups = query("SELECT id, name FROM `groups` WHERE `id` IN ($in) and `deleted_at` is null")->fetch_all();

        $button = [[]];
        foreach ($groups as $result) {
            $button[0][] = ["text" => "🏪 $result[1]", "callback_data" => "group_$result[0]"];
        }
        $button = array_chunk($button[0], 2);

        $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

        $bot->sendMessage($chat_id, "Sizning guruhlaringiz",null, false, null, $b);
    }

});


