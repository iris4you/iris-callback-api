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

		if ($in == 'ping' || $in == 'пинг'  || $in == 'пінг'  || $in == 'пінґ') {
				$getVkTime = $vk->vkRequest('utils.getServerTime',''); /* надо токен */
				$time = (isset($getVkTime["response"])) ? $getVkTime["response"]:time();
				$vk->chatMessage($chatId, "PONG\n" .($time - $message['date']). " сек");
				echo 'ok';
				return;
		}

		if ($in == 'др' || $in == '+др' || $in == '+друг' || $in  == 'дружба' || $in  == '+дружба') {
				$res = $vk->vkRequest('friends.add', 'user_id='.$id);
				if (isset($res["response"])) {
						$msg = UB_ICON_SUCCESS . ' ok';
				} elseif($r = $res["error"]) {
						$msg = UB_ICON_WARN . $r["error_msg"];
				if ($r["error_code"] == 174) $msg = UB_ICON_WARN . ' ВК не разрешает дружить с собой';
				if ($r["error_code"] == 175) $msg = UB_ICON_WARN . ' Удилите дежурного из ЧС! ' . UB_ICON_WARN; 
				if ($r["error_code"] == 176) $msg = UB_ICON_WARN . ' Вы в ЧС у дежурного (наверное за дело?!)'; }
				$vk->chatMessage($chatId, $msg);
				echo 'ok';
				return;
		}

		$vk->chatMessage($chatId, 'Мне прислали сигнал. От пользователя @id' . $object['from_id'], ['disable_mentions' => 1]);
		echo 'ok';
	}

}