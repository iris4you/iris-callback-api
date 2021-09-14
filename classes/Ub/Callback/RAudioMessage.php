<?php
//upd:2021/09/12
class UbCallbackRAudioMessage implements UbCallbackAction {

	function ShowText($text) {
		@header('Content-type: application/json; charset=utf-8', true);
		echo json_encode(['response' => 'ok', 'transcript' => $text], JSON_UNESCAPED_UNICODE);
		return;
	}

	function recogniseAudioMessage($userId, $object, $userbot, $message) {
		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		$localId = (int)@$object['local_id'];
		$vk = new UbVkApi($userbot['token']);

		if(!$localId) {
			UbUtil::echoError('no data', UB_ERROR_NO_DATA);
			return;
		}

		$message = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $localId);

		if (isset($message['error'])) {
				return UbUtil::echoErrorVkResponse($res['error']);
		}
		
		$message = $message['response']['items'][0];
		if(!isset($message['attachments'])) {
				return UbUtil::echoError('!isset($message[attachments])', UB_ERROR_NO_DATA);
		}
		$attach = $message['attachments'][0];
		$type = $attach['type'];
		if($type != 'audio_message') {
				return UbUtil::echoError("$type != audio_message", UB_ERROR_NO_DATA);
		}

		$_arr =$attach["$type"];
		if (isset($_arr['transcript']) && (string)@$_arr['transcript_state'] == 'done') {
				/* дежурный отправит ответом на гс*/
				$msg = (string)@$_arr['transcript'];
				$opt = ['reply_to'=>$message['id']];
				$vk->chatMessage($chatId,$msg,$opt);
				self::ShowText($_arr['transcript']);
				return;
		} else {
			sleep(1);
			self::recogniseAudioMessage($userId, $object, $userbot, $message);
		}

	}

	function execute($userId, $object, $userbot, $message) {
		$localId = (int)@$object['local_id'];

		if(!$localId) {
			UbUtil::echoError('no data', UB_ERROR_NO_DATA);
			return;
		} else {
			self::recogniseAudioMessage($userId, $object, $userbot, $message);
		}
	}
}