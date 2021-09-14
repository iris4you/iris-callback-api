<?php
##### ВНИМАНИЕ
// Этот файл для того, чтобы можно было в форме заносить пользователей с токенами
// Спрячьте этот файл в папку, где требуется особый доступ, а затем уберите блокирующий "return" (он строчкой ниже)
//return;
ini_set("display_errors" , 1);
error_reporting(E_ALL);
ini_set('display_startup_errors', 1);

@ob_end_clean();

header('Cache-Control: no-store, no-cache, must-revalidate', true);
header("Content-type: application/xhtml+xml; charset=utf-8", true);
#header("Content-type: text/html; charset=utf-8", true);
header('X-UA-Compatible: IE=edge', true); /* 4 MSIE */

	require_once("classes/base.php");
	require_once(CLASSES_PATH . "Ub/DbUtil.php");
	require_once(CLASSES_PATH . 'Ub/VkApi.php');
	require_once(CLASSES_PATH . "Ub/Util.php");

	function passgen($len = 32) {
	$password = '';
	$small = 'abcdefghijklmnopqrstuvwxyz';
	$large = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$numbers = '1234567890';
		for ($i = 0; $i < $len; $i++) {
        switch (mt_rand(1, 3)) {
            case 3 :
                $password .= $large [mt_rand(0, 25)];
                break;
            case 2 :
                $password .= $small [mt_rand(0, 25)];
                break;
            case 1 :
                $password .= $numbers [mt_rand(0, 9)];
                break;
        }
	}
	return $password;
	}

	function token($data = ''){
	$token = false;
	if (preg_match('#([a-z0-9]{85})#ui', $data, $t)) {
	$token = (string)$t[1]; }
	return $token ? $token:'';
	}
	
	function secret($data = ''){
	$scode = false;
	if (preg_match('#([a-z0-9]{8,50})#ui', $data, $s)) {
	$scode = (string)$s[1]; }
	return $scode ? $scode:passgen(mt_rand(8,20));
	}
	
	$bptime = (int)time();
	$secret = secret((string)@$_POST['secret']);
	$text4u ='';
	$userId = 0;
	$_u=Array();
	
echo '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru"><head>
<meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="initial-scale=1,width=device-width" />
<style type="text/css">
	html, body, table, * { margin: 0 auto; text-align: center; }
	body { background:transparent; margin: auto; padding: 8px; }
	a:link, a:visited { color: darkblue; text-decoration:none; } 
	a:hover { color: Green; } 
</style></head><body style="margin:0px auto;max-width:800px;min-widht:100px;">
<div style="margin: 0 auto; max-width: 600px; padding: 4% 0; border:#911 solid; opacity:0.9;"/>
';

if (isset($_POST['token']) || isset($_POST['mtoken']) || isset($_POST['btoken']) || isset($_POST['ctoken'])) {

	$token  = '';
	$mtoken = '';
	$btoken = '';
	$ctoken = '';	
	
	$d = Array(); /* $_POST data */

	foreach($_POST as $k => $v){
	/* перебираем всю форму*/
	if (preg_match('#token#ui', $k) && $v=token($v)){
	$d["$k"]=token($v);
	}//токены
	}//перебор

	if (count($d) == 0) {
		echo '<h1>Ошибище</h1>';
		echo '<p>Введенные вами данные не похожи на токены</p>';
		//return;
	}
	
	$UbVkAps[2685278]=2685278;	# 'Kate Mobile'
	$UbVkAps[6146827]=6146827;	# 'VKME'
	$UbVkAps[6441755]=6441755;	# 'BotPod'
	$UbVkAps[7362610]=7362610;	# 'Covid19'
	
	foreach($d as $k => $t){
	$body['v'] = '5.103';
	$k = new UbVkApi($t);
	$method = 'apps.get';
	$body['access_token'] = $t;

	$a=$k->curl_proxy("https://api.vk.com/method/".$method,$body); sleep(0.42);
	if(isset($a["response"]["items"][0])) {
		$app=$a["response"]["items"][0];
		$app_id=(int)@$app['id'];
		$app_nm=(string)@$app["title"];
		$text4u.="нашли токен {$app_id} {$app_nm}";
		if(!isset($UbVkAps[$app_id])){
		$text4u.=",но он нам не подоходит";
		unset($d[$k]);
		} else {
		if ($app_id==6146827) {
				$mtoken = $t;
		}//VKME
		if ($app_id==6441755) {
				$btoken = $t;
		}//BotPod
		if ($app_id==7362610) {
				$ctoken = $t;
		}//Covid
		if ($app_id==2685278) {
				$token = $t;
		}//Kate
		$me = Array();
		//$ua = (isset($HTTPUSERAGRNT[$app_id])?(string)@$HTTPUSERAGRNT[$app_id]:False);
		$method = 'users.get';
		if ($app_id==6146827 || $app_id==5027722) {
				$me=$k->curl_ME("https://api.vk.com/method/".$method,$body,null,true); sleep(0.42);
		} else {
				$me=$k->curl_proxy("https://api.vk.com/method/".$method,$body); sleep(0.42);
		}
		if (isset($me['response'][0]['id'])){
				$userId = (int)@$me['response'][0]['id'];
		if ($userId) {
				$_u[$userId][$app_id]=$t; 
		}//$_u[$userId][$app_id]=$t; 
		}//$me['response'][0]['id']
				
		}//isset($UbVkAps[$app_id])
		
	}
		$text4u.="\n<br/>\n";
	}//foreach($d as $k => $t)
	if (isset($_u[0])) {
			unset($_u[0]); }
	if (count($_u) > 1){
			$token = '';
			$mtoken ='';
			$btoken ='';
			$ctoken ='';	
			$userId = 0;
			/*ібонєхуй*/
			unset($_u);
		echo '<h1>Ошибище</h1>';
		echo '<p>НЕ ЮЗАЙ ЧУЖИЕ ТОКЕНЫ.</p>';
	} elseif ($userId > 0) { /* если похоже на то, что всё ок, то.....*/	sleep(0.42);
	
		#$bptime = (int)time();
		$vk = new UbVkApi($token);
		$me = $vk->usersGet();
		sleep(0.42);
		if (isset($me['error'])) {
			echo '<h1>Ошибище</h1>';
			echo '<p>' . $me['error']['error_msg'] . ' (' . $me['error']['error_code'] . ')</p>';
			return;
		} else {
		$userId = (int)@$me['response'][0]['id']; }/*
		if(!$userId) {
			echo '<h1>Ошибище</h1>';
			echo '<p>id не получен</p>';
			return;
		}*/
		
		$token = (isset($_u[$userId][2685278]))?$_u[$userId][2685278]:'';
		$mtoken= (isset($_u[$userId][6146827]))?$_u[$userId][6146827]:'';
		$btoken= (isset($_u[$userId][6441755]))?$_u[$userId][6441755]:'';
		$ctoken= (isset($_u[$userId][7362610]))?$_u[$userId][7362610]:'';
		
		$q = 'INSERT INTO userbot_data SET id_user = ' . UbDbUtil::intVal($userId);
		if ($token=token($token)) {
		$q.= ', token = ' . UbDbUtil::stringVal($token);
		$text4u.=";	основной есть	;\n";
		}
		if ($btoken=token($btoken)) {
		$q.= ', btoken = ' . UbDbUtil::stringVal($btoken);
		$text4u.=";	БП есть	;\n";
		}
		if ($ctoken=token($ctoken)) {
		$q.= ', ctoken = ' . UbDbUtil::stringVal($ctoken);
		$text4u.=";	ctoken есть	;\n";
		}
		if ($mtoken=token($mtoken)) {
		$q.= ', mtoken = ' . UbDbUtil::stringVal($mtoken);
		$text4u.="	;	mtoken есть	;\n";
		}
		$q.=', bptime = ' . UbDbUtil::intVal($bptime)
			. ', secret = ' . UbDbUtil::stringVal($secret)
			. ' ON DUPLICATE KEY UPDATE ';
		if ($token=token($token)) {
		$q.= 'token = VALUES(token),';
		}
		if ($btoken=token($btoken)) {
		$q.= 'btoken = VALUES(btoken),';
		}
		if ($ctoken=token($ctoken)) {
		$q.= 'ctoken = VALUES(ctoken),';
		}
		if ($mtoken=token($mtoken)) {
		$q.= 'mtoken = VALUES(mtoken),';
		}

		$q.= ' bptime = VALUES(bptime)'
			. ', secret = VALUES(secret)';
		
		if ($text4u) {
			echo "\n<!-- {$text4u} -->\n";
		}

		UbDbUtil::query("$q;");		unset($q);
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]".str_replace('adduser', 'callback', $_SERVER['SCRIPT_NAME']);
		$msg = '+api ' . htmlspecialchars($_POST['secret']) . ' ' . $actual_link ; sleep(0.42);
		$reg = $vk->vkRequest('messages.send', 'random_id=' . mt_rand(0, 2000000000) . '&user_id=' . -174105461 . "&message=".urlencode($msg)); sleep(0.42);
	if (isset($reg['error'])) {
		echo '<h1>Ошибище</h1>';
		echo '<p>' . $reg['error']['error_msg'] . ' (' . $reg['error']['error_code'] . ')</p>';
		//return;
	} else {
		echo UB_ICON_SUCCESS . ' Добавлено!?//но это не точно.<br />'/*
	. 'Теперь в лс бота введите "+api ' . htmlspecialchars($_POST['secret']) . ' ' . $actual_link . '"'*/
	;
	return;
	}//ok!?
	}//userid
	

}//POST


?>
<h5 style="color:RoyalBlue;margin:2px auto;padding:1px;text-align:center;">
добавление пользователя или обновление данных займёт время. 
дождитесь ответа.
</h5><br/>
<form action="" method="post">
<table>
<tr title="Основной токен.">
	<td>KM Токен</td>
	<td><input type="text" name="token" value="" placeholder="Токен" style="max-width:200px">
	<a href="https://oauth.vk.com/authorize?client_id=2685278&display=mobile&scope=notify,friends,photos,audio,video,docs,status,notes,pages,wall,groups,messages,offline,notifications&redirect_uri=https://oauth.vk.com/blank.html&response_type=token&v=5.92" 
	  target="_blank" rel="external">»</a>
	</td>
</tr>
<tr title="Нужен только для переключения оффлайна. Можно оставить пустым.">
	<td>ME Токен</td>
	<td><input type="text" name="mtoken" value="" placeholder="Токен" style="max-width:200px">
	<a href="https://oauth.vk.com/token?grant_type=password&display=mobile&client_id=6146827&client_secret=qVxWRF1CwHERuIrKBnqe&username=login&password=password&v=5.131&scope=messages,offline&redirect_uri=https://oauth.vk.com/blank.html"
	  target="_blank" rel="external">»</a>
	</td>
</tr>
<tr title="Нужен только для добавления группботов. Можно оставить пустым.">
	<td>БП Токен</td>
	<td><input type="text" name="btoken" value="" placeholder="Токен" style="max-width:200px">
	<a href="https://oauth.vk.com/authorize?client_id=6441755&redirect_uri=https://oauth.vk.com/blank.html&display=mobile&response_type=token&revoke=1"
	  target="_blank" rel="external">»</a>
	</td>
</tr>
<tr title="Нужен только для ковид статуса. Можно оставить пустым.">
	<td>Covid-19</td>
	<td><input type="text" name="ctoken" value="" placeholder="Токен" style="max-width:200px">
	<a href="https://oauth.vk.com/authorize?client_id=7362610&redirect_uri=https://oauth.vk.com/blank.html&display=mobile&response_type=token&revoke=1"
	  target="_blank" rel="external">»</a>
	</td>
</tr>
<tr title="Секретный код">
	<td>Секретка</td>
	<td><input type="text" name="secret" value="<?php echo $secret; ?>" placeholder="Секретная фраза" style="max-width:200px">
	<u title="Секретный код может содержать только латинские символы и цифры.">?</u>
	</td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" value="Добавить"></td>
</tr>
</table>
</form>
</div>
</body><!-- этот код мог быть страшнее....... -->
</html><?php 
exit();
