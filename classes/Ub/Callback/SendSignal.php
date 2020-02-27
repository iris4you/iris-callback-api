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

		if ($in == 'ping' || $in == 'пинг'  || $in == 'пінг'  || $in == 'пінґ') {
				$getVkTime = $vk->curl('https://api.vk.com/method/utils.getServerTime');
				$time = (isset($getVkTime["response"])) ? $getVkTime["response"]:time();
				$vk->chatMessage($chatId, "PONG\n" .($time - $message['date']). " сек");
				echo 'ok';
				return;
		}

		$vk->chatMessage($chatId, 'Мне прислали сигнал. От пользователя @id' . $object['from_id'], ['disable_mentions' => 1]);
		echo 'ok';
	}

}