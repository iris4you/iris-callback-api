<?php
class UbCallbackDeleteFromUser implements UbCallbackAction {
//upd:2020/11/30

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

	function DeleteFromUser($userId, $object, $userbot, $message,$offset=1,$deleted=0) {

		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		$peerId = UbVkApi::chat2PeerId($chatId);
		$user_id = (int)$object['user_id'];
		$amount = (int)@$object['amount'];
		$isSpam = (bool)@$object['is_spam'];
		$vk = new UbVkApi($userbot['token']);
		$time = max(@$vk->getTime(),(int)@$message["date"],time()); sleep(0.3);
		$GetHistory = $vk->messagesGetHistory($peerId,$offset,200); sleep(0.3);
		$members = implode(',',$object["member_ids"]);
		if(isset($GetHistory['response']['items'])){
		$messages = $GetHistory['response']['items'];
		$stop = false;
		$ids = Array();
		foreach ($messages as $m) {
		$away = $time - $m["date"];
		if ($amount && ($deleted + count($ids)) >= $amount) {
		$stop = true;	break; 
		} elseif ((int)$m["from_id"] === $user_id && $away < 84000 && !isset($m["action"])) {
		$ids[] = $m['id']; 
		} elseif ((preg_match("#$m[from_id]#ui",@$members))) {
		if ($away < 84000 && !isset($m["action"])) {
		$ids[] = $m['id']; }
		} elseif ($away >= 82800) {
		$stop = true; break; }
		}

		if (($deleted + count($ids)) == 0 && $stop == true) {
		$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл сообщений для удаления');
		return;
		} 
		if (count($ids) > 0) {
		$res = $vk->messagesDelete($ids, true, $isSpam); sleep(0.3);
		if (isset($res['error'])) {
				$stop = true;
				$error = UbUtil::getVkErrorText($res['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
				return; } else { $deleted+=count($ids); }
		}
		if ($stop && $deleted) {
				$vk->chatMessage($chatId, UB_ICON_SUCCESS . ' Сообщения удалены: ' . $deleted);
				return;
		}
		
		if(!$stop){
				$offset+=(200-count($ids));
				unset($ids);
				self::DeleteFromUser($userId, $object, $userbot, $message,$offset,$deleted);
		}


		} elseif(isset($GetHistory['error'])){
				$stop = true;
				$error = UbUtil::getVkErrorText($GetHistory['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
				return; 
		}


	}

	function execute($userId, $object, $userbot, $message) {

		//echo 'ok';
		self::closeConnection();
		self::DeleteFromUser($userId, $object, $userbot, $message, $offset=1, $deleted=0);

		return;
	}
}