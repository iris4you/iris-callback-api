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

		if ($in == 'check_dogs' || $in == 'чек_собак' || $in == 'бан_собак') {
				$raw = $vk->vkRequest('friends.get', "count=5000&fields=deactivated");
				$count = (int)@$raw["response"]["count"];
				$dogs = 0;
				$msg = '';
		if ($count && isset($raw["response"]["items"])) {
				$items = $raw["response"]["items"];

        foreach ($items as $user) {
            $id = $user['id'];
            $name = (string) @$user["first_name"] .' ' . (string) @$user["last_name"];
            $deactivated = (string)@$user["deactivated"];

            if ($deactivated) {
                $dogs++;
            if ($in === 'бан_собак') {

                $del = $vk->vkRequest('friends.delete', 'user_id='.$id);
		                            sleep(1); 
                $ban = $vk->vkRequest('account.ban', 'owner_id='.$id);
		                            sleep(1); 
            }
                $fr = UB_ICON_WARN . " ($deactivated)";
                $msg.=UB_ICON_WARN . " [id$id|$name] $fr\n";

            }

            
            
         }
    }

		if(!$dogs) {
				$msg = 'НЕМА'; } elseif($in == 'бан_собак') {
				$msg.= "спробували видалити $dogs собак (а чи вийшло перевірте)"; }
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
				echo 'ok';
				return;
		}

		$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
		echo 'ok';
	}

}