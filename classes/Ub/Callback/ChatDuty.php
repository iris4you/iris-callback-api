<?php
class UbCallbackChatDuty implements UbCallbackAction {

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

	function execute($userId, $object, $userbot, $message) {

		$vk = new UbVkApi($userbot['token']);
		$chat = (string)@$object['chat'];
		$duty = (int)@$object['duty_id'];

		$get = $vk->vkRequest('friends.areFriends', 'user_ids=' . $duty);
		$are = (int)@$get["response"][0]["friend_status"];

		if ($are == 3) { self::closeConnection(); }

		if ($are == 0 || $are == 2) {
				$add = $vk->vkRequest('friends.add', 'user_id=' . $duty);

				if(!isset($add['error'])) {		self::closeConnection(); }

				if (isset($add['error'])) {
				$error = UbUtil::getVkErrorText($add['error']);
				if ($add['error']["error_code"] == 176) {
				$del = $vk->vkRequest('account.unban', 'user_id=' . $duty); sleep(1);
				$add = $vk->vkRequest('friends.add', 'user_id=' . $duty); sleep(1);
				if(!isset($add['error'])) {		self::closeConnection(); }
				} else {
				$vk->SelfMessage(UB_ICON_WARN . " не удалось добавить @id$duty (дежурный в $chat)\n$error");
				UbUtil::echoErrorVkResponse($add['error']); }
				}
		}

		sleep(9); // подождём… предполагается, что в случае успешного добавления уже успеем связать… */

		UbDbUtil::query("UPDATE `userbot_bind` SET `id_duty` = '$object[duty_id]' WHERE `code` = '$object[chat]';");

		return;
	}
}