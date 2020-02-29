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
				$are = $vk->AddFriendsById($id);
				if ($are == 3) {
						$msg = UB_ICON_SUCCESS . ' ok';
				} elseif ($are == 1) {
						$msg =  UB_ICON_INFO . ' отправлена заявка/подписка пользователю @id' . $id;
				} elseif ($are == 2) {
						$msg =  UB_ICON_SUCCESS . ' заявка одобрена';
				} elseif ($are == 4) {
						$msg =  UB_ICON_WARN . ' повторная отправка заявки';
				} elseif(is_array($are)) {
						$msg = UB_ICON_WARN . $are["error_msg"];
				if ($are["error_code"] == 174) $msg = UB_ICON_WARN . ' ВК не разрешает дружить с собой';
				if ($are["error_code"] == 175) $msg = UB_ICON_WARN . ' Удилите дежурного из ЧС! ' . UB_ICON_WARN; 
				if ($are["error_code"] == 176) $msg = UB_ICON_WARN . ' Вы в ЧС у дежурного (наверное за дело?!)'; }

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

		$vk->chatMessage($chatId, 'Мне прислали сигнал. От пользователя @id' . $object['from_id'], ['disable_mentions' => 1]);
		echo 'ok';
	}

}