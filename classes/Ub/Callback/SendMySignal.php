<?php
class UbCallbackSendMySignal implements UbCallbackAction {
//upd:2021/09/12
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
		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		self::closeConnection();

		$vk = new UbVkApi($userbot['token']);
		$in = $object['value']; // наш сигнал
		#time = $vk->getTime(); // ServerTime
		$time = time(); # время этого сервера

		/* ping служебный сигнал для проверки работоспособности бота *
		 * начиная с первых версий форка отображает время за сколько сигнал дошел сюда *
		 * вариант с микротаймом хоть и приемлем, но не будет более точным, как многие считают,
		 * ибо время сообщения всёравно целое число, да и по времени вк, а не нашего сервера …
		 * так что логичнее оперировать целыми числами, отнимая от времени ВК время сообщения */
		if ($in == 'ping' || $in == 'пинг' || $in == 'пінг' || $in == 'пінґ' || $in == 'зштп') {
				$time = $vk->getTime(); /* ServerTime — текущее время сервера ВК */
				$vk->chatMessage($chatId, "PONG\n" .($time - $message['date']). " сек");
				return;
		}

		/* обновить название чата в базе данных */
		if ($in == 'обновить' || $in == 'оновити') {
				$getChat = $vk->getChat($chatId);
				$chat = $getChat["response"];
				$upd = "UPDATE `userbot_bind` SET `title` = '$chat[title]' WHERE `code` = '$object[chat]';";
				UbDbUtil::query($upd);
				return;
		}

		/* информация о чате */
		if ($in == 'info' || $in == 'інфо' || $in == 'інфа' || $in == 'инфо' || $in == 'инфа') {
		$chat = UbDbUtil::selectOne('SELECT * FROM userbot_bind WHERE id_user = ' . UbDbUtil::intVal($userId) . ' AND code = ' . UbDbUtil::stringVal($object['chat']));
		$getChat = $vk->getChat($chatId);
		if(!$chat['title']) {
				$chat['title'] = (isset($getChat["response"]["title"]))?(string)@$getChat["response"]["title"]:'';
				$upd = "UPDATE `userbot_bind` SET `title` = '$chat[title]' WHERE `code` = '$object[chat]';";
				UbDbUtil::query($upd); }
		$msg = "💬 Chat id: $chatId\n";
		$msg.= "ℹ Iris id: $object[chat]\n";
		$msg.= "🏷 Chat title: $chat[title]\n";
		if ($chat['id_duty']) {
		$msg.= "👤 Дежурный: @id$chat[id_duty]\n"; }
		$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
		return;
		}

		/* приватность онлайна (mtoken от vk,me) */
		if (preg_match('#оффлайн#ui', $in)) {
				//$status - nobody(оффлайн для всех), all(Отключения оффлайна), friends(оффлайн для всех, кроме друзей)
				$token = (isset($userbot['mtoken']))?$userbot['mtoken']:$userbot['token'];
				$status='';
				sleep(0.3);
				if ($in=='-оффлайн')$status = 'all';
				if ($in=='+оффлайн')$status='friends';
				if ($in=='++оффлайн')$status='nobody';
				if ($status == 'all' || $status == 'friends' || $status == 'nobody') {
				$res =  $vk->onlinePrivacy($status, $token); sleep(0.45);
				if (isset($res['error'])) {
				$msg = UB_ICON_WARN . ' ' . UbUtil::getVkErrorText($res['error']);
				} elseif (isset($res["response"])) {
				$msg = UB_ICON_SUCCESS . ' ' . (string)@$res["response"]["category"];
				} else { $msg = UB_ICON_WARN . ' ' . json_encode(@$res); }
				$vk->chatMessage($chatId, $msg); }
				return;
		}

		/* удалить свои сообщения (количество) */
		if (preg_match('#^-смс([0-9\ ]{1,4})?#', $in, $c)) {
				$amount = (int)@$c[1];
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']); sleep(0.3);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё
				if ($mid) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS_OFF . " удаляю сообщения ..."); sleep(0.3); }
				$GetHistory = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200); sleep(0.3);
				if (isset($GetHistory['error'])) {
				$error = UbUtil::getVkErrorText($GetHistory['error']);
				if ($mid) {
				$edit = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId),$mid,$error); 
				if(!isset($edit['error'])) { return; }
				}
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
				return;	}
				$messages = $GetHistory['response']['items'];
				$ids = Array();
				foreach ($messages as $m) {
				$away = $time-$m["date"];
				if ((int)$m["from_id"] == $userId && $away < 84000 && !isset($m["action"])) {
				$ids[] = $m['id']; 
				if ($amount && count($ids) >= $amount) break;				}
				}
				if (!count($ids) && $mid) {
				$vk->messagesDelete($mid, true); 
				return; }

				$res = $vk->messagesDelete($ids, true); sleep(0.3);
				if ($mid) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, count($ids)); sleep(0.3);
				$vk->messagesDelete($mid, true); }
				return;
		}

		/* установка коронавирусного статуса (смайлик возле имени) */
		if (preg_match('#setCovidStatus ([0-9]{1,3})#ui',$message['text'],$s)) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id'];
				$set = $vk->setCovidStatus((int)@$s[1], @$userbot['ctoken']);
				if (isset($set['error'])) {
				$error = UbUtil::getVkErrorText($set['error']);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . ' ' . $error); 
				} elseif(isset($set['response'])) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS); 
				}
				return;
		}

		/* когда был(и) обновлен(ы) токен(ы)// бптокен или все */
		if ($in == 'бпт' || $in == 'бптайм' || $in == 'bptime') {
				$ago = time() - (int)@$userbot['bptime'];
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id'];
				if(!$userbot['bptime']) { 
				$msg = UB_ICON_WARN . ' не задан';
				} elseif($ago < 59) {
				$msg = "$ago сек. назад";
				} elseif($ago / 60 > 1 and $ago / 60 < 59) {
				$min = floor($ago / 60 % 60);
				$msg = $min . ' минут' . self::number($min, 'а', 'ы', '') . ' назад';
				} elseif($ago / 3600 > 1 and $ago / 3600 < 23) {
				$min = floor($ago / 60 % 60);
				$hour = floor($ago / 3600 % 24);
				$msg = $hour . ' час' . self::number($hour, '', 'а', 'ов') . ' и ' .
				$min . ' минут' . self::number($min, 'а', 'ы', '') . ' тому назад';
				} else {
				$msg = UB_ICON_WARN . ' более 23 часов назад';
				$vk->SelfMessage("$msg"); sleep(1); }
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg);
				return;
		}

		/* .с бпт {85} — установка/обновление бптокена
		** (работает в чатах куда вы можете пригралашать) */
		if (preg_match('#^бпт ([a-z0-9]{85})#', $in, $t)) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id'];
				$res = $vk->addBotToChat('-174105461', $chatId, $t[1]);
				#res = $vk->addBotToChat('-182469235', $chatId, $t[1]);
				if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				if ($error == 'Пользователь уже в беседе') {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS); 
				$setbpt = 'UPDATE `userbot_data` SET `btoken` = '.UbDbUtil::stringVal($t[1]).', `bptime` = ' . UbDbUtil::intVal(time()).' WHERE `id_user` = ' . UbDbUtil::intVal($userId);
				$upd = UbDbUtil::query($setbpt);
				$vk->messagesDelete($mid, true); } else 
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . ' ' . $error); }
				return;
		}

		/* .с ст {85} — установка/обновление covid token */
		if (preg_match('#^ст ([a-z0-9]{85})#', $in, $t)) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id'];
				$set_ct = 'UPDATE `userbot_data` SET `ctoken` = '.UbDbUtil::stringVal($t[1]).' WHERE `id_user` = ' . UbDbUtil::intVal($userId);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS); 
				$upd = UbDbUtil::query($set_ct);
				$vk->messagesDelete($mid, true);
				//echo 'ok';
				return;
		}

		/* Ирис в {число} — пригласить Ирис в чат {номер} */
		if (preg_match('#(Iris|Ирис) в ([0-9]+)#ui', $in, $c)) {
				$res = $vk->addBotToChat('-174105461', $c[2], @$userbot['btoken']);
				if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error); }
				$vk->messagesSetMemberRole($c[2], '-174105461', $role = 'admin');
				return;
		}

		/* закрепить пересланное сообщение */
		if ($in == 'закреп' || $in == '+закреп') {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']); sleep(0.45); /* пам'ятаємо про ліміти */
				$mid = (int)@$msg['response']['items'][0]['id'];
				$fwd = []; /* массив. всегда. чтоб count($fwd) >= 0*/
		if (isset($msg["response"]["items"][0]["fwd_messages"])) {
				$fwd[0] = $msg["response"]["items"][0]["fwd_messages"]; }

		if (isset($msg["response"]["items"][0]["reply_message"])) {
				$fwd[0] = $msg["response"]["items"][0]["reply_message"]; }

		if(!count($fwd)) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . ' Не нашёл шо закрепить?!');
				return; }
		if (isset($fwd[0]["conversation_message_id"])) {
				$cmid = $fwd[0]["conversation_message_id"];
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $cmid); sleep(0.45);
				if (isset($msg['error'])) {
				$msg = UB_ICON_WARN . ' ' . UbUtil::getVkErrorText($msg['error']);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				return; }
				$pid = (int)@$msg['response']['items'][0]['id'];
				if(!self::isMessagesEqual($fwd[0], $msg['response']['items'][0])) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN); 
				return; }
				$pin = $vk->messagesPin(UbVkApi::chat2PeerId($chatId), $pid); sleep(0.5);
				if (isset($pin['error'])) {
				$msg = UB_ICON_WARN . ' ' . UbUtil::getVkErrorText($pin['error']);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				} return; } else {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN); 
				}
				return;
		}

		/* открепить закреплённое сообщение */
		if ($in == '-закреп' || $in == 'unpin') {
				$unpin = $vk->messagesUnPin(UbVkApi::chat2PeerId($chatId)); sleep(0.5);
				if (isset($unpin['error'])) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']); sleep(0.45); /* пам'ятаємо про ліміти */
				$mid = (int)@$msg['response']['items'][0]['id'];
				$msg = UB_ICON_WARN . ' ' . UbUtil::getVkErrorText($unpin['error']);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				}
				return;
		}

		/* найти и переслать (количество) упоминаний в чате */
		if (preg_match('#^уведы([0-9\ ]{1,4})?#', $in, $c)) {
				$amount = (int)@$c[1];
				if(!$amount)$amount=5;
				$res = $vk->messagesSearch("id$userId", $peerId = 2000000000 + $chatId, $count = 100);
				if (isset($res['error'])) {
				$error = UbUtil::getVkErrorText($res['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
				return; }
				$ids=[];
				if((int)@$res["response"]["count"] == 0) {
				$vk->chatMessage($chatId, 'НЕМА'); 
				return; }
				foreach ($res['response']['items'] as $m) {
				$away = $time-$m["date"];
				if(!$m["out"] && $away < 84000 && !isset($m["action"])) {
				$ids[]=$m["id"];
				if ($amount && count($ids) >= $amount) break; }
				}
				if(!count($ids)) {
				$vk->chatMessage($chatId, 'НЕМА'); 
				return; }

				$vk->chatMessage($chatId, '…', ['forward_messages' => implode(',',$ids)]);

				return;
		}

		if (preg_match('#дов#ui',$in)) {
				/* работа с сигналами, содержащие "дов" */
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']); sleep(0.4);
				$mid = (int)@$msg['response']['items'][0]['id'];
				$act = False; /* шо ваще делать? */
				
				if ($in == 'доверенные' || $in == 'довереные' || $in == 'довірені' || $in == 'довы') {
				$opt = ['disable_mentions' => 1, 'dont_parse_links' => 1];
				if(!$userbot['access']) {
				$msg = ' ПУСТО.';
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return;
				}#нету.
				$usersGet = $vk->usersGet($userbot['access']); sleep(0.34);
				if (isset($usersGet['error'])) {
				$msg = UbUtil::getVkErrorText($usersGet['error']);
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return;
				}
				if (isset($usersGet['response'])) {
				$users = $usersGet['response'];
				$msg = "📃 ДОСТУП НАДАНО:\n";
        foreach ($users as $user) {
            
            $id = (int)$user["id"]; // id юзера для посилання на профіль
            $name=self::for_name(@$user["first_name"] .' ' . @$user["last_name"]);
            $msg.= "👤 [id$id|$name]\n"; // list item для списку

        }

				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return;
				}
				return;
				}#довы

				
		if ($in == 'мдовы') {
				$q = "SELECT `id_user` FROM `userbot_data` WHERE `userbot_data`.`access` LIKE '%$userId%' AND `userbot_data`.`id_user` > '0' ORDER by `userbot_data`.`id_user` ASC;";
				$u = UbDbUtil::select($q);
				if(!$u) {
				$msg = UB_ICON_WARN . ' ПУСТО.';
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return; }
				$ids = '';
				foreach ($u as $item) {
				  $ids = ($ids=='')? $item['id_user']:"$ids,$item[id_user]";
				}

				$usersGet = $vk->usersGet($ids); sleep(0.34);
				if (isset($usersGet['error'])) {
				$msg = UB_ICON_WARN . UbUtil::getVkErrorText($usersGet['error']);
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return;
				}
				if (isset($usersGet['response'])) {
				$users = $usersGet['response'];
				$msg = "📃 Вам доверяют:\n";
        foreach ($users as $user) {
            
            $id = (int)$user["id"]; // id юзера для посилання на профіль
            $name=self::for_name(@$user["first_name"] .' ' . @$user["last_name"]);
            $msg.= "👤 [id$id|$name]\n"; // list item для списку

        }

				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return;

				}
				return;
				}#мдовы

				$ids = []; /* массив. всегда. чтоб count($ids) >= 0*/
				if (isset($msg["response"]["items"][0]["fwd_messages"])) {
				foreach($msg["response"]["items"][0]["fwd_messages"] as $m) {
				$ids[$m["from_id"]]=$m["from_id"];
				}//Айдишки авторов пересланных
				}
				if (isset($msg["response"]["items"][0]["reply_message"])) {
				$id=$msg["response"]["items"][0]["reply_message"]["from_id"];
				$ids[$id] = $id; }
				if (count($ids) == 0) {
				$msg = "!! ОТВЕТ ИЛИ ПЕРЕСЫЛ !!";
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg); 
				return;
				}

		if ($in == '+дов' || $in == '-дов') {

				$msg = '';
				$dov = $userbot['access'];

				foreach($ids as $id) {
				
				if ($in == '+дов' && $id > 0) {
				if(!preg_match("#$id#ui",$dov)){
				$dov.= ",$id"; }
				$msg.= UB_ICON_SUCCESS ." $in @id$id\n";
				}
				
				if (preg_match("#$id#ui",$dov) && $in == '-дов' && $id != $userId) {
				$sho = array(",$id,",",$id","$id,",",,");
				$na  = array(",", "", "", ",");
				$dov = str_replace($sho,$na,$dov);
				$msg.= UB_ICON_SUCCESS_OFF." $in @id$id\n";
				}
				
				}//foreach($ids as $id)
				
				if ($dov != $userbot['access']){
				UbDbUtil::query("UPDATE `userbot_data` SET `access` = '$dov' WHERE `id_user` = '$userId'");
				}

				if ($msg) {
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg); 
				if(!isset($r['error'])) { return; }
				}
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
				return; }
				return;
		}//±дов(ы);
		
		

		}/* работа с сигналами, содержащие "дов" */


########################################################################

		/* вступление в чат по ссылке на чат. будьте осторожны с этим сигналом: 
		** во многих чатах запрещены ссылки на чаты. лучше не используйте сигнал */
		if (preg_match('#https?://vk.me/join/([A-Z0-9\-\_\/]{24})#ui',$message['text'],$l)) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё
				$New = $vk->joinChatByInviteLink($l[0]);
				if (is_numeric($New)) {
				$msg = UB_ICON_SUCCESS . " $New ok";
				$vk->chatMessage($New,'!связать'); sleep(2.5);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg);
				} else { $msg = UB_ICON_WARN . " $New";
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . @$New); }
				//echo 'ok';
				return;
		}

########################################################################

		if ($in == 'патоген') {
				sleep(0.34);
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё

				for ($i = 1; $i <= 10; $i++) {
				sleep(0.34);
				$msg = '.лаб в лс';
				$sms = $vk->vkRequest('messages.send', 'random_id=' . mt_rand(0, 2000000000) . '&user_id=' . -174105461 . "&message=".urlencode($msg)); sleep(0.34);
				if (isset($sms['response'])) {
				$smsid = (int)@$sms['response'];
				if ($smsid) { $vk->messagesDelete($smsid, true); }
				}
				sleep(0.34);
				$getOneMSG = $vk->vkRequest('messages.getHistory', 'peer_id=-174105461&count=1'); sleep(0.34);
				if (isset($getOneMSG['response']['items'][0]['text'])) {
				$iristxt = $getOneMSG['response']['items'][0]['text'];
				$newtext = '';
				if (preg_match('#🔬 Досье лаборатории (.*)\:\nРуководитель: \[id([0-9]+)\|(.*)\]\n#ui', $iristxt, $t)) {
				$vk->vkRequest('messages.markAsRead', 'peer_id=-174105461'); sleep(0.34);# чтоб не приходила уведа
				if (preg_match('#🧪 Готовых патогенов: ([0-9]+) из ([0-9]+)#ui', $iristxt, $t)) {
				$newtext.= "🎯 Снарядов: $t[1] из $t[2]\n"; }
				if (preg_match('#Новый патоген: (.*)\n#ui', $iristxt, $t)) {
				$newtext.= "⏳ Новый снаряд: $t[1]\n"; }
				if (preg_match('#Био-опыт: (.*)\n#ui', $iristxt, $t)) {
				$newtext.= "✨ Опыт: $t[1]\n\n"; }
				if ($newtext) {
				if (preg_match('# в состоянии горячки.+#ui', $iristxt, $t)) {
				$newtext.= UB_ICON_WARN . $t[0]; 
				if (preg_match('#вызванной болезнью «(.+)»#ui', $iristxt, $p)) {
				$getText="заражению патогеном «$p[1]»";sleep(0.34);
				$res = $vk->messagesSearch("$getText", $peerId = null, $count = 100); sleep(0.3);
				if (isset($res['response']['items'])) {	
				foreach($res['response']['items'] as $item){
				if (preg_match("#\[id([0-9]+)\|(.*)\] (подверг|подвергла) заражению патогеном «(.+)»#ui", 
				$item['text'], $t)) {
				$p_uid=(int)@$t[1];
				$pname=(string)@$t[4];
				$newtext=preg_replace("#$pname#ui","[id{$p_uid}|$pname]",$newtext,1); 
				if ($pname == $p[1]){ $rplsd = True; break; }
				}//preg_match("#\[id([0-9]+)\|(.*)\] (подверг|подвергла) заражению патогеном «$p[1]»#ui"
				}	// item			
				} //items
				} //#вызванной болезнью
				} else {
				$newtext.= UB_ICON_SUCCESS . " горячка не найдена";				}
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $newtext); 
				if(!isset($r['error'])) { break; return; }
				sleep(0.34);
				}
				$s = $vk->chatMessage($chatId, $newtext); 
				if(!isset($s['error'])) { break; return; }
				sleep(0.34);
				} //N=1
				} //L=1
				} //R=1
				} //i++
				//error?
				return;
		}	//патоген

		if (preg_match('#(пп|ген|патоген) (.{2,42})#ui', $message['text'], $p)) {
				$msg=$vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId),$object['conversation_message_id']);
				$mid=(int)@$msg['response']['items'][0]['id'];
				$newtext = ''; # ніфіга,або іншше.
				$pfinded = Array(); # пустий масив
				$is_ok = 0;
				$citms = 0;
				$getText="заражению патогеном «{$p[2]}»"; sleep(0.3);
				$res = $vk->messagesSearch("$getText", $peerId = null, $count = 100); sleep(0.3);
				if (isset($res['response']['items'])) {
				$newtext = UB_ICON_INFO." {$p[0]}:\n";//find
				$pfinded = Array();
				foreach($res['response']['items'] as $item){
				if(preg_match("#\[id([0-9]+)\|(.*)\] (подверг|подвергла) заражению патогеном «(.+)»#ui",
				$item['text'],$t)){
				$p_uid=(int)@$t[1];
				$pname=(string)@$t[4];
				if(!isset($pfinded[$p_uid]) && $pname == $p[2]) {
				$newtext.="\n🏷 «[id{$p_uid}|{$pname}]»";
				$pfinded[$p_uid]=$pname;
				$is_ok+=1; }	//is_ok++
				}	//preg_match
				}	// as $item
				}//items
				if(!$newtext || !$is_ok || count($pfinded)==0){
				$newtext = "❗ Патоген «{$p[2]}» не известен"; }
				if (count($pfinded) > 0){
				$newtext = UB_ICON_INFO." {$p[0]}:\n";//find
				foreach ($pfinded as $p_uid => $pname)	{
				$newtext.="\n🏷 «[id{$p_uid}|{$pname}]»";}
				} //$pfinded
				if ($mid) {
				$r = $vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $newtext); 
				if(!isset($r['error'])) { return; }
				sleep(0.34);
				}
				$s = $vk->chatMessage($chatId, $newtext); 
				return;
		}//#^пп .*

		$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
	}

    static function for_name($text) {
        return trim(preg_replace('#[^\pL0-9\=\?\!\@\\\%/\#\$^\*\(\)\-_\+ ,\.:;]+#ui', '', $text));
    }

    static function isMessagesEqual($m1, $m2) {
		return ($m1['from_id'] == $m2['from_id'] && $m1['conversation_message_id'] == $m2['conversation_message_id']/* && $m1['text'] == $m2['text']*/);
    }

    static function number($num, $one, $two, $more) {
        $num = (int)$num;
        $l2 = substr($num, strlen($num) - 2, 2);

        if ($l2 >= 5 && $l2 <= 20)
            return $more;
        $l = substr($num, strlen($num) - 1, 1);
        switch ($l) {
            case 1:
                return $one;
                break;
            case 2:
                return $two;
                break;
            case 3:
                return $two;
                break;
            case 4:
                return $two;
                break;
            default:
                return $more;
                break;
        }
    }

}