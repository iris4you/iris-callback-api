<?php
class UbCallbackSendMySignal implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {
		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		$vk = new UbVkApi($userbot['token']);
		$in = $object['value']; // наш сигнал

		if ($in == 'ping' || $in == 'пинг'  || $in == 'пінг'  || $in == 'пінґ') {
				$getVkTime = $vk->vkRequest('utils.getServerTime',''); /* надо токен */
				$time = (isset($getVkTime["response"])) ? $getVkTime["response"]:time();
				$vk->chatMessage($chatId, "PONG\n" .($time - $message['date']). " сек");
				echo 'ok';
				return;
		}

		if ($in == 'link' || $in == 'лінк') {
				$msg = $vk->messagesGetInviteLink($chatId);
				if (preg_match('#^https?://vk#ui', $msg)) {
				$setlink = "UPDATE `userbot_bind` SET `link` = '$msg' WHERE `code` = '$object[chat]'";
				UbDbUtil::query($setlink); } else {
				$msg = 'https://github.com/S1S13AF7/iris-callback-api'; }
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
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

		$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
		echo 'ok';
	}

}