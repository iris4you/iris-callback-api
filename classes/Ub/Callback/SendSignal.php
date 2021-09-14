<?php

/**
 * @const TIME_START Время запуска скрипта в миллисекундах
 */
	if(!defined('TIME_START')) {
	    define ('TIME_START', microtime(true)); // время запуска скрипта
	}

class UbCallbackSendSignal implements UbCallbackAction {

	function closeConnection() {
		@ob_end_clean();
		@header("Connection: close");
		@ignore_user_abort(true);
		@ob_start();
		echo 'ok';
		$size = ob_get_length();
		@header("Content-Length: $size");
		@ob_end_flush(); // All output buffers must be flushed here
		@flush(); // Force output to client
	}

	function execute($userId, $object, $userbot, $message) {
		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		self::closeConnection();

		$vk = new UbVkApi($userbot['token']);
		$in = $object['value']; // сам сигнал
		$id = $object['from_id']; // от кого
		$time = time(); # время этого сервера
		#time = $vk->getTime(); // ServerTime
		$tag = ($id<0)?'@club'.(-1 * $id):'@id'.$id; /* упонание @ */
		$options = ['disable_mentions' => 1,'dont_parse_links' => 1];
		$CanCtrl = (bool)(preg_match("#$id#ui",@$userbot['access']));
		if ((int)@$object['from_id'] == (int)$userId)$CanCtrl = True;

		if ($in == 'ping' || $in == 'пинг' || $in == 'пінг' || $in == 'пінґ' || $in == 'зштп') {
				$pong= $time - (int)@$message['date'];
				$msg = (isset($message['date']))?"PONG\nсигнал дошел за $pong сек.\n":"🤔 PONG\n";
				$ts1 = microtime(true);
				$msg.= "запрос к вк...\n";
				$mess= $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$te1 = microtime(true);
				$pong= $te1 - $ts1;
				$msg.= "ВК ответил за $pong сек.\n";
				$te0 = microtime(true);
				$pong= $te0 - TIME_START;
				$r_t = (int)$mess['response']['items'][0]['id']; 
				$msg.= "скрипт отработал (включая запрос) за $pong сек.\n";
				$opt = ($r_t) ? ['reply_to'=>$r_t]:['disable_mentions'=>1];
				$send = $vk->chatMessage($chatId, $msg, $opt);
				return;
		}

		if ($in == 'ping?' || $in == 'пинг?' || $in == 'пінг?' || $in == 'пінґ?') {
				$msg = "🤔 PONG";
				$mess= $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$r_t = (int)$mess['response']['items'][0]['id']; 
				$opt = ($r_t) ? ['reply_to'=>$r_t]:['disable_mentions'=>1];
				$send = $vk->chatMessage($chatId, $msg, $opt);
				return;
		}

		if ($in == 'обновить' || $in == 'оновити') {
				$getChat = $vk->getChat($chatId);
				$chat = $getChat["response"];
				$upd = "UPDATE `userbot_bind` SET `title` = '$chat[title]', `id_duty` = '". UbDbUtil::intVal($userbot['id_user']) ."' WHERE `code` = '$object[chat]';";
				UbDbUtil::query($upd);
				return;
		}

		if ($in == 'info' || $in == 'інфо' || $in == 'інфа' || $in == 'инфо' || $in == 'инфа') {
		$chat = UbDbUtil::selectOne('SELECT * FROM userbot_bind WHERE id_user = ' . UbDbUtil::intVal($userId) . ' AND code = ' . UbDbUtil::stringVal($object['chat']));
		$getChat = $vk->getChat($chatId);
		if(!$chat['title'] || $chat['id_duty'] != $userId) {
				$chat['title'] = (isset($getChat["response"]["title"]))?(string)@$getChat["response"]["title"]:'';
				$upd = "UPDATE `userbot_bind` SET `title` = '$chat[title]', `id_duty` = '". UbDbUtil::intVal($userbot['id_user']) ."' WHERE `code` = '$object[chat]';";
				UbDbUtil::query($upd); }

		$msg = "💬 Chat id: $chatId\n";
		$msg.= "ℹ Iris id: $object[chat]\n";
		$msg.= "🏷 Chat title: $chat[title]\n";
		$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
		return;
		}

		if ($in == '-смс') {
				$GetHistory = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200);
				$messages = $GetHistory['response']['items'];
				$ids = Array();
				foreach ($messages as $m) {
				$away = $time - $m["date"];
				if ((int)$m["from_id"] == $userId && $away < 84000 && !isset($m["action"])) {
				$ids[] = $m['id']; }
				}
				if (!count($ids)) {
				#$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл сообщений для удаления');
				return; }

				$res = $vk->messagesDelete($ids, true);

				return;
		}

		if (preg_match('#^-смс ([0-9]{1,3})#', $in, $c)) {
				$amount = (int)@$c[1];
				$GetHistory = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200);
				$messages = $GetHistory['response']['items'];
				$ids = Array();
				foreach ($messages as $m) {
				$away = $time - $m["date"];
				if ((int)$m["from_id"] == $userId && $away < 84000 && !isset($m["action"])) {
				$ids[] = $m['id']; 
				if ($amount && count($ids) >= $amount) break;				}
				}
				if (!count($ids)) {
				#$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл сообщений для удаления');
				return; }

				$res = $vk->messagesDelete($ids, true);

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
				/*$vk->SelfMessage("$msg"); sleep(1); */
				}
				$vk->chatMessage($chatId, $msg);
				return;
		}

		if (preg_match('#^(Iris|Ирис) в ([0-9]+)#ui', $in, $c)) {
				$res = $vk->addBotToChat('-174105461', $c[2], @$userbot['btoken']);
				if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error); }
				return;
		}

		if (preg_match('#^(добавь|верни) в ([a-z0-9]{8})#ui', $in, $c)) {
				$toChat = UbDbUtil::selectOne('SELECT * FROM userbot_bind WHERE id_user = ' . UbDbUtil::intVal($userId) . ' AND code = ' . UbDbUtil::stringVal($c[2]));

		if(!$toChat) {
				$vk->chatMessage($chatId,  UB_ICON_WARN . ' no bind chat ' . $c[2]);
				return; }

				$res = $vk->messagesAddChatUser($object['from_id'], $toChat['id_chat'], @$userbot['btoken']);
		if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
				}

				return;

		}

		/* повтор текста */
		if (preg_match('#(повтори|скажи|напиши)#ui',$message['text'],$t)) {
				$txt=preg_replace('#.д\s(повтори|скажи|напиши|бомба)\s#ui','',$message['text'],1);
				if(!$CanCtrl) { $txt=UB_ICON_INFO . " @id$id просит сказать:\n".self::substr($txt,256,0,'…'); }
				elseif (preg_match('#лаб#ui',$txt)) {	$txt = '.с патоген';	}
				if (preg_match('#-игра|-биоигра#ui',$txt)) {	$txt=UB_ICON_INFO . " @id$id хочет в скам";	}
				$opt=['disable_mentions' => 1, 'dont_parse_links' => 1];
				$vk->chatMessage($chatId, $txt, $opt); 
				return;
		}

		/* инфа|вероятность. Если задан mtoken будет отправлена "бомба" */
		if (preg_match('#(инфа|інфа|вероятность)(.+)#ui',$message['text'],$t)) {
				$txt=self::substr($t[2],3007, $start=0, $mn = '…'); // ібо нєхуй
				$txt= UB_ICON_INFO . " @id$id верноятность, что $txt ". mt_rand(0,101) . '%';
				$opt=['disable_mentions' => 1, 'dont_parse_links' => 1];
				$vk->chatMessage($chatId, $txt, $opt); 
				return;
		}

		if ($in == 'ферма') {
				$txt = '💬 Чтобы добывать ирис-коины перейдите в пост https://m.vk.com/wall-174105461_6713149
				и введите команду "ферма"';
				$vk->chatMessage($chatId, $txt);
				return;
		}

		$vk->chatMessage($chatId, 'Мне прислали сигнал. От пользователя ' . $tag, ['disable_mentions' => 1]);
	}

    static function for_name($text) {
        return trim(preg_replace('#[^\pL0-9\=\?\!\@\\\%/\#\$^\*\(\)\-_\+ ,\.:;]+#ui', '', $text));
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

    /**
     * Получение подстроки
     * Корректная работа с UTF-8
     * @param string $text Исходная строка
     * @param integer $len Максимальная длина возвращаемой строки
     * @param integer $start Начало подстроки
     * @param string $mn Текст, подставляемый в конец строки при условии, что возхвращаемая строка меньще исходной
     * @return string
     */
    function substr($text, $len, $start = 0, $mn = '') {
        $text = trim($text);
        if (function_exists('mb_substr')) {
            return mb_substr($text, $start, $len) . (mb_strlen($text) > $len - $start ? $mn : null);
        }
        if (function_exists('iconv')) {
            return iconv_substr($text, $start, $len) . (iconv_strlen($text) > $len - $start ? $mn : null);
        }

        return $text;
    }

}