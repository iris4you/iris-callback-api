<?php
//upd:2021/09/04 (не помню що, возможно тупо бэкап, но
//upd:2021/09/14: поубирал то, что уже/пока не работает
class UbCallbackLpMySignal implements UbCallbackAction {

	function closeConnection() {
		@ob_end_clean();
		@header("Connection: close");
		@ignore_user_abort(true);
		@ob_start();
		//echo 'ok';
		@header('Content-type: application/json; charset=utf-8', true);
		echo json_encode(['response' => 'ok'], JSON_UNESCAPED_UNICODE);
		$size = ob_get_length();
		@header("Content-Length: $size");
		@ob_end_flush(); // All output buffers must be flushed here
		@flush(); // Force output to client
	}

	function execute($userId, $object, $userbot, $message) {
		self::closeConnection();

		$vk = new UbVkApi($userbot['token']);
		$in = $object['value']; // наш сигнал
		#time = $vk->getTime(); // ServerTime
		$time = time(); # время этого сервера

		$chatId = (int)UbVkApi::peer2ChatId((int)@$message['peer_id']);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		/* ping служебный сигнал для проверки работоспособности бота *
		 * начиная с первых версий форка отображает время за сколько сигнал дошел сюда *
		 * вариант с микротаймом хоть и приемлем, но не будет более точным, как многие считают,
		 * ибо время сообщения всёравно целое число, да и по времени вк, а не нашего сервера …
		 * так что логичнее оперировать целыми числами, отнимая от времени ВК время сообщения */
		if ($in == 'ping' || $in == 'пинг' || $in == 'пінг' || $in == 'пінґ' || $in == 'зштп') {
				$time = $vk->getTime(); /* ServerTime — текущее время сервера ВК */
				$pong = "PONG\n " . ($time - $message['date']) . " сек";
				$vk->chatMessage($chatId, $pong);
				return;
		}

		/* назначить администратором (как у Ириса; если есть право назначать админов) *
		if ($in == '+admin' || $in == '+адмін' || $in == '+админ' || $in == '+фвьшт') {
				$ids = $vk->GetUsersIdsByFwdMessages($chatId, $object['conversation_message_id']);
				if(!count($ids)) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл пользователей');
				return; } elseif(count($ids) > 3) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' может не стоит делать много админов?');
				return; }
				foreach($ids as $id) {
				$res=$vk->messagesSetMemberRole($chatId, $id, $role = 'admin');
				if(isset($res['error'])) { $vk->chatMessage($chatId,UB_ICON_WARN.$res["error"]["error_msg"]); }
				}

				return;

		}*/

		/* забрать у пользователя админку (не в Ирисе, а ВК) *\
		if ($in == '-admin' || $in == '-адмін' || $in == '-админ' || $in == '-фвьшт' || $in == 'снять') {
				$ids = $vk->GetUsersIdsByFwdMessages($chatId, $object['conversation_message_id']);
				if(!count($ids)) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл пользователей');
				return; }
				foreach($ids as $id) {
				$res=$vk->messagesSetMemberRole($chatId, $id, $role = 'member');
				if(isset($res['error'])) { $vk->chatMessage($chatId,UB_ICON_WARN.$res["error"]["error_msg"]); }
				sleep(1);
				}

				return;

		}*/

		/* добавить в друзья. Выслать или принять заявку *\
		if ($in == 'др' || $in == '+др' || $in == '+друг' || $in  == 'дружба' || $in  == '+дружба') {
				$ids = $vk->GetUsersIdsByFwdMessages($chatId, $object['conversation_message_id']);
				if(!count($ids)) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл пользователей');
				return; } elseif(count($ids) > 5) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Многабукаф,ниасилил');
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
						if ($are["error"]["error_code"] == 174) $fr = UB_ICON_WARN . " ВК не разрешает дружить с собой\n";
						if ($are["error"]["error_code"] == 175) $fr = UB_ICON_WARN . " @id$id Удилите меня из ЧС!\n";
						if ($are["error"]["error_code"] == 176) $fr = UB_ICON_WARN . " @id$id в чёрном списке\n"; }
								sleep($cnt);
								$msg.=$fr;
						}

				if (isset($msg)) {
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
				}

				return;
		}*/

		/* принять в друзья *\
		if ($in == 'прийом') {
				$add = $vk->confirmAllFriends();
				$msg = $add ? '+'.$add : 'НЕМА';
				$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);
				return;
		}

		/* отклонить заявки / отписаться *\
		if ($in == 'отмена' || $in == 'отписка') {
				$del = $vk->cancelAllRequests();
				$msg = $del ? "скасовано: $del": 'НЕМА';
				$vk->chatMessage($chatId, $msg);
				return;
		}

		/* проверить наличие "собак" *\
		if ($in == 'check_dogs' || $in == 'чек_собак') {
		$res = $vk->getChat($chatId, 'deactivated');
		$all = $res["response"]["users"];
		$msg ='';
		$dogs= 0;

        foreach ($all as $user) {
            
            $name= (string)@$user["first_name"] .' ' . (string) @$user["last_name"];
            $dog = (string)@$user["deactivated"];

            if ($dog) {
                $dogs++; 
                $del = $vk->DelFriendsById($user["id"]);

                $msg.= "$dogs. [id$user[id]|$name] ($dog)\n";
            }

         }

         if(!$dogs) {
            $msg = 'НЕМА'; }
		$vk->chatMessage($chatId, $msg, ['disable_mentions' => 1]);

		$friends = $vk->vkRequest('friends.get', "count=5000&fields=deactivated");
		$count = (int)@$friends["response"]["count"];
				$dogs = 0;
				$msg = '';
		if ($count && isset($friends["response"]["items"])) {
				$items = $friends["response"]["items"];

        foreach ($items as $user) {
            
            $name= (string) @$user["first_name"] .' ' . (string) @$user["last_name"];
            $dog = (string)@$user["deactivated"];

            if ($dog) {
                $dogs++; 
                $del = $vk->DelFriendsById($user["id"]);
                $msg.= "$dogs. [id$user[id]|$name] ($dog)\n";
            }
         }
    }

		if ($dogs) { $vk->SelfMessage($msg); }
				return;
		}*\

		/* приватность онлайна (mtoken от vk,me) */
		if ($in == '+оффлайн' | $in == '-оффлайн') {
				//$status - nobody(оффлайн для всех), all(Отключения оффлайна), friends(оффлайн для всех, кроме друзей)
				$token = (isset($userbot['mtoken']))?$userbot['mtoken']:$userbot['token'];
				$status = ($in == '-оффлайн')? 'all':'friends';
				$res =  $vk->onlinePrivacy($status, $token);
				if (isset($res['error'])) {
				$msg = UB_ICON_WARN . ' ' . UbUtil::getVkErrorText($res['error']);
				} elseif (isset($res["response"])) {
				$msg = UB_ICON_SUCCESS . ' ' . (string)@$res["response"]["category"];
				} else { $msg = UB_ICON_WARN . ' ' . json_encode(@$res); }
				$vk->chatMessage($chatId, $msg); 
				return;
		}

		/* удалить свои */
		if ($in == '-смс') {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS_OFF . " удаляю сообщения ...");
				$GetHistory = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200);
				$messages = $GetHistory['response']['items'];
				$ids = Array();
				foreach ($messages as $m) {
				$away = $time-$m["date"];
				if ((int)$m["from_id"] == $userId && $away < 84000 && !isset($m["action"])) {
				$ids[] = $m['id']; }
				}
				if (!count($ids)) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, ' Не нашёл сообщений для удаления');
				$vk->messagesDelete($mid, true); 
				return; }

				$res = $vk->messagesDelete($ids, true);

				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, count($ids));
				$vk->messagesDelete($mid, true); 
				return;
		}

		/* удалить свои сообщения (количество) */
		if (preg_match('#^-смс ([0-9]{1,3})#', $in, $c)) {
				$amount = (int)@$c[1];
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS_OFF . " удаляю сообщения ...");
				$GetHistory = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200);
				$messages = $GetHistory['response']['items'];
				$ids = Array();
				foreach ($messages as $m) {
				$away = $time-$m["date"];
				if ((int)$m["from_id"] == $userId && $away < 84000 && !isset($m["action"])) {
				$ids[] = $m['id']; 
				if ($amount && count($ids) >= $amount) break;				}
				}
				if (!count($ids)) {
				$vk->messagesDelete($mid, true); 
				return; }

				$res = $vk->messagesDelete($ids, true);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, count($ids));
				$vk->messagesDelete($mid, true); 
				return;
		}

		/* рд (количество) ['РедачДелит']; */
		if (preg_match('#^рд([0-9\ ]{1,4})?#', $in, $c)) {
				$amount = (int)@$c[1];
				if(!$amount)$amount=5;
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_SUCCESS_OFF); sleep(0.5);
				$GetHistory = $vk->messagesGetHistory(UbVkApi::chat2PeerId($chatId), 1, 200);
				$messages = $GetHistory['response']['items'];
				$ids = Array();
				foreach ($messages as $m) {
				$away = $time-$m["date"];
				if ((int)$m["from_id"] == $userId && $away < 84000 && !isset($m["action"])) {
				$r=$vk->messagesEdit(UbVkApi::chat2PeerId($chatId),$m['id'],'&#13;');
				$ids[] = $m['id']; 
				$st = (count($ids)+1)/10;
				sleep($st);
				if (isset($r['error'])) {
				sleep(count($ids)+2);
				$err = UbUtil::getVkErrorText($r['error']);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId),$mid,count($ids).' ; '.$err);
				sleep(count($ids)+2); }
				if ($amount && count($ids) >= $amount) break;				}
				}
				if (!count($ids)) {
				$vk->messagesDelete($mid, true); 
				return; }

				$res = $vk->messagesDelete($ids, true);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, count($ids));
				$vk->messagesDelete($mid, true); 
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
				$msg = UB_ICON_WARN . ' более 23 часов назад';/*
				$vk->SelfMessage("$msg");*/ sleep(1); }
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
				return;
		}

		/* Адреса сервера */
		if ($in == 'сервер') {
				$vk->chatMessage($chatId, $_SERVER['HTTP_HOST'], ['dont_parse_links' => 1]);
				return; 
		}

		/* повтор текста или "бомба" (если сигнал бомба и задан mtoken) */
		if (preg_match('#(повтори|скажи|напиши|бомба)(.+)#ui',$message['text'],$t)) {
				$opt=['disable_mentions' => 1, 'dont_parse_links' => 1];
				if (isset($userbot['mtoken']) && @$userbot['mtoken']!='' && preg_match('#^бомба#ui',$in)) {
				$opt=['disable_mentions' => 1, 'dont_parse_links' => 1, 'expire_ttl' => 84000]; 
				$vk = new UbVkApi($userbot['mtoken']); }
				$vk->chatMessage($chatId, $t[2], $opt); 
				return;
		}

		/* закрепить пересланное сообщение */
		if ($in == 'закреп' || $in == '+закреп') {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']); sleep(0.5); /* пам'ятаємо про ліміти, бля! */
				$mid = (int)@$msg['response']['items'][0]['id'];
				/* далі йде копія $vk->GetFwdMessagesByConversationMessageId($peerId = 0, $conversation_message_id = 0) */
				$fwd = []; /* массив. всегда. чтоб count($fwd) >= 0*/
		if (isset($msg["response"]["items"][0]["fwd_messages"])) {
				$fwd = $msg["response"]["items"][0]["fwd_messages"]; }

		if (isset($msg["response"]["items"][0]["reply_message"])) {
				$fwd[]=$msg["response"]["items"][0]["reply_message"]; }

		if(!count($fwd)) {
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . ' Не нашёл шо закрепить?!');
				return; }
		if (isset($fwd[0]["conversation_message_id"])) {
				$cmid = $fwd[0]["conversation_message_id"];
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $cmid); sleep(0.5);
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
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']); sleep(0.5); /* пам'ятаємо про ліміти, бля! */
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

		/* отчёты вашей сб в игре коронаирис (кол-во) */
		if (preg_match('#^сб([0-9\ ]{1,4})?#', $in, $c)) {
				//тоже del
				return;
		}

		/* вступление в чат по ссылке на чат. будьте осторожны с этим сигналом: 
		** во многих чатах запрещены ссылки на чаты. лучше не используйте сигнал */
		if (preg_match('#https?://vk.me/join/([A-Z0-9\-\_\/]{24})#ui',$message['text'],$l)) {
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё
				$New = $vk->joinChatByInviteLink($l[0]);
				if (is_numeric($New)) {
				$msg = UB_ICON_SUCCESS . " $New ok";
				$vk->chatMessage($New,'!связать'); sleep(5);
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, $msg);
				UbDbUtil::query("UPDATE `userbot_bind` SET `link` = '$l[0]' WHERE `id_user` = '$userId' AND `id_chat` = '$New'");
				$vk->SelfMessage("$New\n$l[0]");
				} else { $msg = UB_ICON_WARN . " $New";
				$vk->messagesEdit(UbVkApi::chat2PeerId($chatId), $mid, UB_ICON_WARN . @$New); }
				//echo 'ok';
				return;
		}

########################################################################

		if ($in == 'патоген') {
				#$time = time();
				#$pong = (isset($message['date']))?($time - (int)@$message['date']):0;
				#$trys = 0;
				
				sleep(0.34);
				$msg = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $object['conversation_message_id']);
				$mid = (int)@$msg['response']['items'][0]['id']; // будем редактировать своё

				for ($i = 1; $i <= 16; $i++) {
				//echo $i;
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
				//_vk_.method('messages.markAsRead', {'peer_id': -174105461})# чтоб не приходила уведа
				$vk->vkRequest('messages.markAsRead', 'peer_id=-174105461'); sleep(0.34);
				if (preg_match('#🧪 Готовых патогенов: ([0-9]+) из ([0-9]+)#ui', $iristxt, $t)) {
				$newtext.= "🎯 Снарядов: $t[1] из $t[2]\n"; }
				if (preg_match('#Новый патоген: (.*)\n#ui', $iristxt, $t)) {
				$newtext.= "⌛ Новый снаряд: $t[1]\n"; }
				if (preg_match('#Био-опыт: (.*)\n#ui', $iristxt, $t)) {
				$newtext.= "✨ Опыт: $t[1]\n\n"; }
				if ($newtext) {
				if (preg_match('# в состоянии горячки.+#ui', $iristxt, $t)) {
				$newtext.= UB_ICON_WARN . $t[0]; } /*else {
				$newtext.= UB_ICON_SUCCESS . " горячка не найдена";				}*/
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
		}

########################################################################

		/*	ну крч тут тоже почти всё нафиг выбросить или переделать	*/
		/*	кст будет работать с более старой версией из папки .zip	*/

########################################################################


		#$vk->chatMessage($chatId, UB_ICON_WARN . ' ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН');
		return;
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