<?php
class UbCallbackSendSignal implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {
		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		$vk = new UbVkApi($userbot['token']);
		$in = $object['value']; // сам сигнал
		$id = $object['from_id']; // от кого
		$fr = $vk->areFriendsById($id);

		if ($in == 'ping' || $in == 'пинг'  || $in == 'пінг'  || $in == 'пінґ') {
				$getVkTime = $vk->vkRequest('utils.getServerTime',''); /* надо токен */
				$time = (isset($getVkTime["response"])) ? $getVkTime["response"]:time();
				$vk->chatMessage($chatId, "PONG\n" .($time - $message['date']). " сек");
				echo 'ok';
				return;
		}

		if ($in == 'др' || $in == '+др' || $in == '+друг' || $in  == 'дружба' || $in  == '+дружба') {
				$ids = $vk->GetUsersIdsByFwdMessages($chatId, $object['conversation_message_id']);
				$ids[$id] = $id; /*+дружба с самим юзером, независимо от наличия "fwd_messages" */

				if(count($ids) > 6) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Многабукаф,ниасилил');
				echo 'ok';
				return; }

				$msg = '';
				$cnt = 0;

				foreach($ids as $id) {
								$fr='';
								$cnt++;
				$are = $vk->AddFriendsById($id);
				if ($are == 3) {
								$fr = UB_ICON_SUCCESS . " @id$id ok\n";
				} elseif ($are == 1) {
								$fr =  UB_ICON_INFO . " отправлена заявка/подписка пользователю @id$id\n";
				} elseif ($are == 2) {
								$fr =  UB_ICON_SUCCESS . " заявка от @id$id одобрена\n";
				} elseif ($are == 4) {
								$fr =  UB_ICON_WARN . " повторная отправка заявки @id$id\n";
				} elseif(is_array($are)) {
								$fr = UB_ICON_WARN . " $are[error_msg]\n"; 
						if ($are["error_code"] == 174) $fr = UB_ICON_WARN . " ВК не разрешает дружить с собой\n";
						if ($are["error_code"] == 175) $fr = UB_ICON_WARN . " @id$id Удилите дежурного из ЧС!\n";
						if ($are["error_code"] == 176) $fr = UB_ICON_WARN . " @id$id Вы в ЧС у дежурного\n"; }
								sleep($cnt);
								$msg.=$fr;
						}

				if (isset($msg)) {
						$vk->chatMessage($chatId, $msg);
				}

				echo 'ok';
				return;
		}

		if ($in == 'прийом') {
				$add = $vk->confirmAllFriends();
				$msg = $add ? '+'.$add : 'НЕМА';
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
				echo 'ok';
				return;
		}

		if ($in == 'отмена' || $in == 'отписка') {
				$del = $vk->cancelAllRequests();
				$msg = $del ? "скасовано: $del": 'НЕМА';
				$vk->chatMessage($chatId, $msg);
				echo 'ok';
				return;
		}

		if ($in == 'обновить' || $in == 'оновити') {
				$getChat = $vk->getChat($chatId);
				$chat = $getChat["response"];
				$upd = "UPDATE `userbot_bind` SET `title` = '$chat[title]', `id_duty` = '". UbDbUtil::intVal($userbot['id_user']) ."'".((preg_match('#^https?://vk.me/join/([A-Z0-9\-\_]{24})#ui', $vk->messagesGetInviteLink($chatId), $l))?", `link` = '$l[0]'":'')." WHERE `code` = '$object[chat]';";
				UbDbUtil::query($upd);
				//$vk->chatMessage($chatId, $msg);
				echo 'ok';
				return;
		}

		if ($in == '-смс') {
				$getVkTime = $vk->vkRequest('utils.getServerTime',''); /* надо токен */
				$time = (isset($getVkTime["response"])) ? $getVkTime["response"]:time();
				$messages = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200, $options = []);
				$messages = $messages['response']['items'];
				$ids = [];
				foreach ($messages as $m) {
				$away = $time - $m["date"];
				if ($m["from_id"]==$userbot['id_user'] && $away < 84600)
				$ids[] = $m['id'];
				}
				if (!count($ids)) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл сообщений для удаления');
				echo 'ok';
				return; }

				$res = $vk->messagesDelete($ids, true);

				if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
				echo 'ok';
				return;
				}
				echo 'ok';
				return;
		}

		if ($in == 'бпт' || $in == 'бптайм'  || $in == 'bptime') {
				$ago = time() - (int)@$userbot['bptime'];
				if(!$userbot['bptime']) { 
				$msg = UB_ICON_WARN . ' не задан';
				} elseif($ago < 59) {
				$msg = "$ago сек. назад";
				} elseif($ago / 60 > 1 and $ago / 60 < 59) {
				$min = floor($ago / 60 % 60);
				$msg = $min . ' минут' . self::number($min, 'а', 'ы', '') . ' назад';
				} elseif($ago / 3600 > 1 and $ago / 3600 < 23) {
				$min = floor($ago / 60 % 60);
				$hour = floor($ago / 3600 % 24);
				$msg = $hour . ' час' . self::number($hour, '', 'а', 'ов') . ' и ' .
				$min . ' минут' . self::number($min, 'а', 'ы', '') . ' тому назад';
				} else {
				$msg = UB_ICON_WARN . ' более 23 часов назад';
				}
				$vk->chatMessage($chatId, $msg);
				echo 'ok';
				return;
		}

		$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
		echo 'ok';
	}

    static function number($num, $one, $two, $more) {
        $num = (int)$num;
        $l2 = substr($num, strlen($num) - 2, 2);

        if ($l2 >= 5 && $l2 <= 20)
            return $more;
        $l = substr($num, strlen($num) - 1, 1);
        switch ($l) {
            case 1:
                return $one;
                break;
            case 2:
                return $two;
                break;
            case 3:
                return $two;
                break;
            case 4:
                return $two;
                break;
            default:
                return $more;
                break;
        }
    }

}