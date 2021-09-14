<?php

/**
 * @const TIME_START Время запуска скрипта в миллисекундах
 */
	if(!defined('TIME_START')) {
	    define ('TIME_START', microtime(true)); // время запуска скрипта
	}

class UbCallbackLpLdSignal implements UbCallbackAction {

	function closeConnection() {
		@ob_end_clean();
		@header("Connection: close");
		@ignore_user_abort(true);
		@ob_start();
		//echo 'ok';
		@header('Content-type: application/json; charset=utf-8', true);
		echo json_encode(['response' => 'ok'], JSON_UNESCAPED_UNICODE);
		$size = ob_get_length();
		@header("Content-Length: $size");
		@ob_end_flush(); // All output buffers must be flushed here
		@flush(); // Force output to client
	}

	function execute($userId, $object, $userbot, $message) {
		self::closeConnection();

		$vk = new UbVkApi($userbot['token']);
		$in = @$object['value'];// наш сигнал
		$id = (int)@$object['from_id'];//from
		#time = $vk->getTime(); // ServerTime
		$time = time(); # время этого сервера
		$tag = ($id<0)?'@club'.(-1 * $id):'@id'.$id; /* упонание @ */
		$CanCtrl = (bool)(preg_match("#$id#ui",@$userbot['access']));
		if ((int)@$object['from_id'] == (int)$userId)$CanCtrl = True;

		$chatId = (int)UbVkApi::peer2ChatId((int)@$message['peer_id']);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		/* ping служебный сигнал для проверки работоспособности бота */
		if ($in == 'ping' || $in == 'пинг' || $in == 'пінг' || $in == 'пінґ') {
				#$time = $vk->getTime(); /* ServerTime — текущее время сервера ВК */ sleep(0.3);
				$mess= $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$r_t = (int)$mess['response']['items'][0]['id']; 
				$opt = ($r_t)?['reply_to'=>$r_t]:['disable_mentions'=>1];
				$pong = "PONG!\n " . ($time - $message['date']) . " сек";
				if ((int)@$object['from_id'] == (int)$userId && $r_t > 0) {
				$edit = $vk->messagesEdit($message['peer_id'],$r_t,$pong);
				if(!isset($edit['error'])) { return; }
				}
				$send = $vk->chatMessage($chatId, $pong, $opt);
				return;
		}

		if(!$CanCtrl) {
				$mess= $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$r_t = (int)@$mess['response']['items'][0]['id']; 
				$opt = ($r_t)?['reply_to'=>$r_t]:['disable_mentions'=>1];
				//$pong = '!ПОГОДА НАХУЙ';#отличная идея (нет);
				$pong = UB_ICON_WARN." у {$tag} нет доступа\n".
				UB_ICON_INFO." Команды кроме пинга запрещены.";
				$send = $vk->chatMessage($chatId, $pong, $opt);
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
				/*$vk->SelfMessage("$msg");*/ sleep(1); }
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
		if (preg_match('#(повтори|скажи|напиши|патоген|пп|ген|кмд|ферма|лаб|связать|api)(.*)#ui',$message['text'],$t)) {
				#$txt=($CanCtrl)?$t[2]: UB_ICON_INFO . " @id$id просит сказать:\n".self::substr($t[2],256,0,'…');
				$opt=['disable_mentions' => 1, 'dont_parse_links' => 1]; $txt='';
				if (preg_match('#(повтори|скажи|напиши|кмд)\n(.+)#ui',$message['text'],$t)) { $txt=$t[2]; }
				if (preg_match('#лаб|патоген#ui',$message['text'])) {	$txt = '.с патоген';	}
				if (preg_match('#(пп|ген|патоген) (.{2,42})#ui', $message['text'], $p)) {	$txt = "!с $p[0]";	}
				if (preg_match('#-игра|-биоигра|передать#ui',$message['text'])){$txt=UB_ICON_INFO." @id$id хочет в скам";	}
				if (preg_match('#api|дежурный#ui',$message['text'])){ $txt=UB_ICON_INFO." напиши нанять 666 @id$userId"; }
				if (preg_match('#связать#ui',$message['text'])) {	$txt = '!связать';	}
				/* список *preg* можно дополнять хоть вечность */
				if ($txt!='')$vk->chatMessage($chatId,$txt,$opt); 
				return;
		}

		$vk->chatMessage($chatId,'Мне прислали сигнал. От пользователя '.$tag,['disable_mentions'=>1]);
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