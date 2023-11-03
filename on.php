<?php

$bot->on(static function (){}, static function(\TelegramBot\Api\Types\Update $update) use ($bot){
    $textChecker = $update->getMessage()->getText();
    $chat_id = $update->getMessage()->getChat()->getId();
    $message_id = $update->getMessage()->getMessageId();
    $user_status = getStatus($chat_id);
    $main_menu_btn = getMainMenuBtn($chat_id);
    //LOGIN
    if ($user_status == ''){
        if (!file_exists("session/$chat_id.txt")){
            if ($textChecker) {
                $telefon_raqam = preg_replace('/[^0-9]/', '', $textChecker);
            }else {
                $number = $update->getMessage()->getContact()->getPhoneNumber();
                $telefon_raqam = preg_replace('/[^0-9]/', '', $number);
            }

            if (strlen($telefon_raqam) == 12){

                $back = backBtn('mobile_number_tasdiqlashga_qaytish');

                $bazada_bor = query("SELECT mobile_number FROM users where mobile_number = $telefon_raqam and deleted_at is null")->num_rows;

                if ($bazada_bor == 1 ){
                    $userID = query("SELECT id FROM users where mobile_number = '$telefon_raqam' and deleted_at is null")->fetch_assoc()['id'];
                    $student_id = query("SELECT id FROM students WHERE user_id = '$userID' and deleted_at is null")->fetch_assoc()['id'];
                    if ($student_id){
                        $myfile = fopen("session/$chat_id.txt", "w+");
                        fwrite($myfile, "student_id=" . $student_id . ";");
                        fclose($myfile);
                        $bot->sendMessage($chat_id, "Muaffaqiyatli ✅ \nParolni kiriting", null, false, null, $back);
                    }else{
                        $bot->sendMessage($chat_id, "❌ Ushbu raqam dasturda mavjud emas ❌", null, false, null, $back);
                    }
                }

                elseif ($bazada_bor == 0){
                    $bot->sendMessage($chat_id, "❌ Ushbu raqam dasturda mavjud emas ❌", null, false, null, $back);
                }

                else{
                    $users_massiv = query("SELECT id FROM users where mobile_number = $telefon_raqam and deleted_at is null")->fetch_all();

                    $in = createSqlIn($users_massiv);
                    $students_massiv = query("SELECT name,filial_id,id FROM students WHERE `user_id` IN ($in) and deleted_at is null")->fetch_all();

                    if (!empty($students_massiv)){
                        $button = [[]];
                        foreach ($students_massiv as $result) {
                            $filial_name = query("SELECT name FROM filials where id = $result[1] and deleted_at is null")->fetch_assoc()['name'];
                            $button[0][] = ["text" => "$result[0] ➡️ $filial_name", "callback_data" => "studentId_$result[2]"];
                        }
                        $button = array_chunk($button[0], 1);
                        $users_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

                        $bot->sendMessage($chat_id, "Ushbu raqamdan bir nechta foydalanuvchi ro'yhatdan o'tgan. Siz kimsiz ?", "HTML", false, null,$users_btn);
                    }else{
                        $bot->sendMessage($chat_id,"Siz hodim sifatida ro'yhatdan o'tkansiz. Sizda ushbu botdan foydalanish imkoniyati mavjud emas!", null, false, null,$back);
                    }
                }
            }else{
                $bot->sendMessage($chat_id, "Telefon raqam xato kiritildi ❌ Na'munaga qarang");
            }

        }else{
            // PAROL KELSA
            $hasSessionStudentId = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

            $user = query("SELECT u.id, u.password FROM users u join students s ON s.user_id = u.id where s.id = $hasSessionStudentId")->fetch_assoc();

            $hash_password = $user['password'];
            $user_id = $user['id'];

            if (password_verify($textChecker, $hash_password)){
                $hash_writeable_chatId = query("SELECT chat_ids_bot FROM users WHERE id = $user_id")->fetch_assoc()['chat_ids_bot'];

                if ($hash_writeable_chatId){
                    insertChatIds($chat_id, $user_id, $hash_writeable_chatId);
                }else{
                    insertChatIds($chat_id, $user_id);
                }
                $bot->sendMessage($chat_id, "Hush kelibsiz!",'HTML');
                $bot->sendMessage($chat_id, "O'zingizga kerakli menyulardan birini tanlang!",'HTML', false, null,  $main_menu_btn);
            }else{
                $bot->sendMessage($chat_id, 'Parol xato kiritildi 🤷‍♂️. Iltimos qaytadan harakat qiling');
            };
        }
    }


    //PROFILIM
    if ($textChecker == 'Profilim 👤' && $user_status == 'login boldi'){
        $hasSessionStudentId = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

        $user_name_and_number = query("SELECT JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS,name,mobile_number,id FROM users WHERE users.chat_ids_bot LIKE '%$chat_id%'")->fetch_assoc();
        $filials = query("SELECT s.filial_id, f.name FROM students s join filials f ON f.id = s.filial_id where s.id = '$hasSessionStudentId'")->fetch_assoc();
        $filial_id = $filials['id'];
        $filial_nomi = $filials['name'];
        $fish = $user_name_and_number['name'];
        $tel_number = $user_name_and_number['mobile_number'];
        $userId = $user_name_and_number['id'];

        $text = "👤 FISH: $fish\n📱 Telefon raqam: $tel_number\n🏪 O'quv markazi: $filial_nomi";

        $sozlamar_btn = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([[['text'=>"Parolni o'zgartirish ✏️", 'callback_data'=>'edit_password']],
            [['text'=>'Hisobdan chiqish ➡️', 'callback_data'=>"logout_$userId"]]]);

        $bot->sendMessage($chat_id, $text, 'HTML', false, null, $sozlamar_btn);
    };

    //PAROLNI TAHRIRLASH
    if ($user_status == 'eski password'){
        $user_hash_password = query("SELECT JSON_EXTRACT(chat_ids_bot, '$.data[*]') AS STATUS, password FROM users WHERE users.chat_ids_bot LIKE '%$chat_id%'")->fetch_assoc()['password'];

        $btn = backBtn('eski_parolga_qaytarish');

        if (password_verify($textChecker, $user_hash_password)){
            updateStatus($chat_id, 'yangi password');

            $bot->sendMessage($chat_id, "Yangi parolni kiriting", null, false, null, $btn);
        }else{
            $bot->sendMessage($chat_id, "❌ Parol xato kiritildi ❌", null,false, null, $btn);
        }
    }

    if ($user_status == 'yangi password'){
        if (strlen($textChecker) >= 4 ){
            updateStatus($chat_id, 'login boldi');
            $new_hash_password = password_hash($textChecker, PASSWORD_BCRYPT);
            query("UPDATE users SET password = '$new_hash_password' WHERE  users.chat_ids_bot LIKE '%$chat_id%'");
            $bot->sendMessage($chat_id, '✅ Parol muaffaqiyatli tahrirlandi ✅', null, false, null, $main_menu_btn);
        }else{
            $bot->sendMessage($chat_id, "⭕️ Parol 4 ta ishoradan kam bo'lmasligi kerak");
        }

    }

    //GURUHLAR
    if ($textChecker == "Guruhlarim 👥" &&  $user_status == 'login boldi'){
//        $user_id = query("SELECT id FROM `users` WHERE  users.chat_ids_bot->>'$.data[*].chat_id' LIKE '%$chat_id%'")->fetch_assoc()['id'];

//        $s = query("SELECT students.id FROM students JOIN users ON users.id = students.user_id WHERE users.id = '$user_id'")->fetch_assoc()['id'];
        $s = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

        $g = query("SELECT group_id FROM group_student WHERE student_id = '$s' and `deleted_at` is null")->fetch_all();

        if (empty($g)){
            $bot->sendMessage($chat_id, "Siz guruhga qo'shilmagansiz",null, false, null, $b);
        }else{
            $in = createSqlIn($g);

            $groups = query("SELECT id, name FROM `groups` WHERE `id` IN ($in) and `deleted_at` is null")->fetch_all();

            $button = [[]];
            foreach ($groups as $result) {
                $button[0][] = ["text" => "🏪 $result[1]", "callback_data" => "group_$result[0]"];
            }
            $button = array_chunk($button[0], 2);

            $b = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($button);

            $bot->sendMessage($chat_id, "Sizning guruhlaringiz",null, false, null, $b);
        }

    }

    //TO'LOVLAR
    if ($textChecker == "To'lovlarim 💵" &&  $user_status == 'login boldi'){
        $s = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

        $tolovlarMassiv = query("SELECT t.*, gr.name FROM `tolovlars` t inner join `groups` gr ON gr.id = t.group_id WHERE t.student_id = '$s' and t.summa > 0 and t.deleted_at is null")->fetch_all();

        if (count($tolovlarMassiv)>0){
            foreach ($tolovlarMassiv as $item){
                $summa = number_format($item[2]);
                $sana = $item[3];
                $group = $item[11];
                $text = "💵 Summa: $summa\n📆 Sana: $sana\n👥 Guruh: $group";

                $bot->sendMessage($chat_id, $text);
            }
        }else{
            $bot->sendMessage($chat_id,"Sizda to'lovlar mavjud emas ☹️\nMenyulardan boshqasini tanlang 👇");
        }


    }

    //SMSlar TARIXI
    if ($textChecker == "SMSlar tarixi 📨" &&  $user_status == 'login boldi'){
        $s = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);

        $tolovlarMassiv = query("SELECT m.* FROM `messages` m WHERE m.messagable_id = '$s' and m.status = 'ha'")->fetch_all();

        if (count($tolovlarMassiv)>0){
            foreach ($tolovlarMassiv as $item){
                $sms = $item[0];
                $sana = $item[4];
                $text = "📆 Sana: $sana\n\n $sms";

                $bot->sendMessage($chat_id, $text);
            }
        }else{
            $bot->sendMessage($chat_id, "Sizda SMS lar mavjud emas ☹️\nMenyulardan boshqasini tanlang 👇");
        }

    }



    //KEYINGI OY UCHUN TOLOVLARNI HISOBLAB BERISH
    if ($textChecker == "💵 Keyingi oy uchun to'lov hisobi 💵" &&  $user_status == 'login boldi'){
        $student_id = str_replace(';','',explode("=", file_get_contents("session/$chat_id.txt"))[1]);
        $g = query("SELECT group_id FROM group_student WHERE student_id = '$student_id' and `deleted_at` is null")->fetch_all();

        if (empty($g)){
            $bot->sendMessage($chat_id, "Siz guruhga qo'shilmagansiz",null, false, null, $b);
        }else {
            $in = createSqlIn($g);
            $groups = query("SELECT id, name, cost FROM `groups` WHERE `id` IN ($in) and `deleted_at` is null")->fetch_all();

            $str = "App\\\Models\\\Student";

            $kashalok = query("SELECT SUM(summa) as balans FROM `kashaloks` WHERE `kashalokable_id` = '$student_id' AND `kashalokable_type`= '$str'")->fetch_assoc()['balans'];


            $is_jarima_required = query("SELECT sozlamalars.summa_for_count FROM sozlamalars join centers ON centers.id = sozlamalars.center_id join filials ON filials.center_id = centers.id join students ON students.filial_id = filials.id WHERE students.id = '$student_id'")->fetch_assoc();
            $summa = $is_jarima_required['summa_for_count'];

            $textStr = "Sizning hozirgi balansingiz: ".number_format($kashalok);
            $bot->sendMessage($chat_id, $textStr);

            foreach ($groups as $result) {
                $group_id = $result[0];
                $balls = query("SELECT count, day FROM balls WHERE student_id = '$student_id' and group_id = '$group_id' and MONTH(day) = MONTH(now()) and YEAR(day) = YEAR(now())")->fetch_all();

                $jarima_count = 0;
                $ragbat_count = 0;

                foreach ($balls as $ball) {
                    if ($ball[0]<0){
                        $jarima_count+=$ball[0];
                    }else{
                        $ragbat_count+=$ball[0];
                    }
                }

                $umumiy = ($jarima_count+$ragbat_count)*intval($summa);
                $guruh_cost = number_format($result[2]);
                $viewJarima = number_format($jarima_count*intval($summa));
                $viewRagbat = number_format($ragbat_count*intval($summa));


                $cost = intval($result[2]) + ($umumiy);

                $str = "👥 Guruh $result[1] summasi: $guruh_cost so'm\n📛 Jarimalar: $viewJarima\n🌟 Rag'batlar:$viewRagbat\n\n💰 Keyingi oy uchun to'lanishi kerak bo'lgan summa: ".number_format($cost);

                $bot->sendMessage($chat_id, $str);
            }

        }
    }

});

