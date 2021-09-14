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

		$vk->chatMessage($chatId,'Мне прислали сигнал. От '.$tag,['disable_mentions'=>1]);
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