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
				UbDbUtil::query($setlink); } elseif ($object['chat'] == '94dfdbd4') {
				$msg = 'https://github.com/S1S13AF7/iris-callback-api'; }
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
				echo 'ok';
				return;
		}

		if ($in == 'др' || $in == '+др' || $in == '+друг' || $in  == 'дружба' || $in  == '+дружба') {
				/* старая версия на случай если с новой не прокатит
				$res = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$fwd = $res["response"]["items"][0]["fwd_messages"];*/
				$fwd = $vk->GetFwdMessagesByConversationMessageId($chatId, $object['conversation_message_id']);
				if(!count($fwd)) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл сообщений');
				echo 'ok';
				return; } elseif(count($fwd) > 7) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Многабукаф,ниасилил');
				echo 'ok';
				return; }
				$msg = '';
				foreach($fwd as $m) {
				$ids[$m["from_id"]]=$m["from_id"]; /* исключаем случаи, когда несколько пересланных от одного id */
				}

				$cnt = 0;

				foreach($ids as $id) {
								$cnt++;
								$are = $vk->AddFriendsById($id);
						if ($are == 3) {
								$msg.= UB_ICON_SUCCESS . " @id$id ok\n";
						} elseif ($are == 1) {
								$msg.=  UB_ICON_INFO . " отправлена заявка/подписка пользователю @id$id\n";
						} elseif ($are == 2) {
								$msg.=  UB_ICON_SUCCESS . " заявка от @id$id одобрена\n";
						} elseif ($are == 4) {
								$msg.=  UB_ICON_WARN . " повторная отправка заявки @id$id\n";
						} elseif(is_array($are)) {
								$msg.= UB_ICON_WARN . " $are[error_msg]\n"; 
						}
								sleep($cnt);
				}
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