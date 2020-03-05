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
				$ids = $vk->GetUsersIdsByFwdMessages($chatId, $object['conversation_message_id']);
				$ids[$id] = $id; /*+дружба с самим юзером, независимо от наличия "fwd_messages" */

				if(count($ids) > 5) {
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

		if ($in == '-смс') {
				$messages = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200, $options = []);
				$messages = $messages['response']['items'];
				$ids = [];
				foreach ($messages as $m) {
				$away = time() - $m["date"];
				if ($m["from_id"]==$userbot['id_user'] && $away < 86400)
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

		$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
		echo 'ok';
	}

}