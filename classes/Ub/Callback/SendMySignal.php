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
				$ids = $vk->GetUsersIdsByFwdMessages($chatId, $object['conversation_message_id']);
				if(!count($ids)) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл пользователей');
				echo 'ok';
				return; } elseif(count($ids) > 5) {
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
						if ($are["error_code"] == 175) $fr = UB_ICON_WARN . " @id$id Удилите меня из ЧС!\n";
						if ($are["error_code"] == 176) $fr = UB_ICON_WARN . " @id$id в чёрном списке\n"; }
								sleep($cnt);
								$msg.=$fr;
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

		if (preg_match('#^бпт ([a-z0-9]{85})#', $in, $t)) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = $msg['response']['items'][0]['id'];
				$res = $vk->addBotToChat('-174105461', $chatId, $t[1]);
				if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				if ($error == 'Пользователь уже в беседе') {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS); 
				$setbpt = 'UPDATE `userbot_data` SET `btoken` = '.UbDbUtil::stringVal($t[1]).', `bptime` = ' . UbDbUtil::intVal(time()).' WHERE `id_user` = ' . UbDbUtil::intVal($userbot['id_user']);
				$upd = UbDbUtil::query($setbpt);
				$vk->messagesDelete($mid, true); } else 
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . ' ' . $error); }
				echo 'ok';
				return;
		}

		$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
		echo 'ok';
	}

}