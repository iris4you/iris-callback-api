<?php
#####
header('Cache-Control: no-store, no-cache, must-revalidate', true);
header('Content-Type: text/html; charset=utf-8', true);
header('X-UA-Compatible: IE=edge', true); /* 4 MSIE */

	#require_once("classes/base.php");
	#require_once(CLASSES_PATH . "Ub/DbUtil.php");
	#require_once(CLASSES_PATH . 'Ub/VkApi.php');
	#require_once(CLASSES_PATH . "Ub/Util.php");

echo '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru"><head>
<meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="generator" content="«S1S13AF7», Notepad2.0.18 MOD, etc" />
<meta name="viewport" content="initial-scale=1,width=device-width" />
<style type="text/css">
	html, body, table, * { background: transparent; color:#111; margin: 0 auto; }
	html { background: transparent; color:#111; padding: 0 1%; text-align: center; }
	body { background: transparent; margin: 0 auto; max-width:800px; padding: 8px; }
	h1, h2, h3, h4, h5, h6, img, div, table, td, tr {
	text-align: center; 
	margin:0px; 
	padding: 0; 
	border:0px; 
	max-width:98%;}
	a:link, a:visited { color: darkblue; text-decoration: none; } 
	a:hover { color: Green; text-decoration: underline; } 
</style></head><body style="margin:0px auto;max-width:800px;min-widht:100px;">
';

?>
<b>Iris Callback API 2.0</b> поддерживает большое количество 
<a href="https://vk.com/@iris_callback-api2" target="_blank">сигналов</a>.
Стандартные сигналы почти все поддерживают все популярные дежурные. 
А вот .д/.с у всех свои. Хотя достаточно популярные сигналы есть почти везде. 
На этой странице будут описаны именно поддерживаемые этим экземляром.
!д .д /д — сигнал дежурному
!с .с /с — себе<br/>
<table style="width:98%">
<tr><td>+</td><td>ok</td></tr>
<tr><td>-</td><td>нет</td></tr>
<tr><td>*</td><td>частично</td></tr>
</table><br/><table style="width:98%">
 <tr>
	<td>сигнал</td>
	<td>инфа</td>
	<td>с</td>
	<td>д</td>
 </tr>
 <tr>
	<td>пинг</td>
	<td>время в секундах, которое прошло с момента отправки команды до того, как команда дошла</td>
	<td>+</td>
	<td>+</td>
 </tr><tr>
	<td>обновить</td>
	<td>обновить инфу о чате в базе (например если сменилось название)</td>
	<td>+</td>
	<td>+</td>
 </tr><tr>
	<td>инфа</td>
	<td>Общая информация о чате</td>
	<td>+</td>
	<td>+</td>
 </tr><tr>
	<td>-смс,<br/>-смс (кол-во)</td>
	<td>удаляет (свои) сообщения. но пока только среди последних 200.</td>
	<td>*</td>
	<td>*</td>
 </tr><tr>
	<td>повтори<br/>скажи<br/>напиши</td>
	<td>дежурный напишет:<br/>@id$id просит сказать:<br/>текст</td>
	<td>-</td>
	<td>*</td>
 </tr><tr>
	<td>инфа<br/>інфа<br/>вероятность</td>
	<td>дежурный напишет:<br/>@id$id верноятность, что $txt mt_rand(0,101)%</td>
	<td>-</td>
	<td>*</td>
 </tr><tr>
 </tr><tr>
	<td>ферма</td>
	<td>Чтобы добывать ирис-коины перейдите в пост и введите команду "ферма"</td>
	<td>*</td>
	<td>*</td>
 </tr><tr>
	<td>+оффлайн<br/>-оффлайн<br/>++оффлайн</td>
	<td>+ переводит в режим друзья<br/>-доступно всем<br/>++совсем оффлайн</td>
	<td>*</td>
	<td>-</td>
 </tr><tr>
	<td>закреп<br/>+закреп<br/>-закреп</td>
	<td>закрепить пересланное сообщение<br/>/анпин<br/>(необходима админка)</td>
	<td>*</td>
	<td>-</td>
 </tr><tr>
	<td>+дов<br/>-дов<br/>довы<br/>мдовы</td>
	<td>выдать/отобрать доверку<br/>список доверок</td>
	<td>*</td>
	<td>-</td>
 </tr><tr>
	<td>патоген</td>
	<td>отобразит лабу (патогены,горячка), не спалив прокачку.</td>
	<td>+</td>
	<td>*</td>
 </tr><tr>
	<td>пп<br/>ген</td>
	<td>поиск владельцов патогена по его названию (.с пп хз)</td>
	<td>+</td>
	<td>-</td>
 </tr><tr>
	<td>.с уведы (кол-во)</td>
	<td>найти и переслать (количество) упоминаний в чате за поседние 24 часа</td>
	<td>+</td>
	<td>-</td>
 </tr><tr>
	<td>https://vk.me/join/*</td>
	<td>вступление в чат по ссылке. будьте осторожны с этим сигналом.</td>
	<td>+</td>
	<td>-</td>
 </tr><tr>
	<td>Ирис в {номер}</td>
	<td>добавить Ириса в чат {номер}/* бптокен должен быть не старее суток.</td>
	<td>*</td>
	<td>*</td>
 </tr><tr>
	<td>бптайм</td>
	<td>сколько прошло с момента установки/обновления бптокена или всех токенов</td>
	<td>+</td>
	<td>+</td>
 </tr><tr>
	<td>бпт {85}</td>
	<td>установка/обновление бптокена (работает в чатах куда вы можете пригралашать)<br />
	важно: если ваш сервер НЕ http://localhost/ прокси должен совпадать с тем, через какой получаем</td>
	<td>*</td>
	<td>-</td>
 </tr><tr>
	<td>ст {85}</td>
	<td>установка/обновление коронавирусного токена<br />
	важно: если ваш сервер НЕ http://localhost/ прокси должен совпадать с тем, через какой получаем</td>
	<td>*</td>
	<td>-</td>
 </tr><tr>
	<td>SetCovidStatus {номер}</td>
	<td>установка коронавирусного статуса (смайлик возле имени)<br />* covid токен должен быть обновлён менее суток назад</td>
	<td>*</td>
	<td>-</td>
 </tr><tr>
	<td></td>
	<td>Мне прислали сигнал. От пользователя @id<br />дежурный получил сигнал. но команду не знает.</td>
	<td></td>
	<td>+</td>
 </tr><tr>
	<td></td>
	<td>⚠️ ФУНКЦИОНАЛ НЕ РЕАЛИЗОВАН<br />— ответ на неизвестную команду</td>
	<td>*</td>
	<td></td>
 </tr>
 </tr>
</table>
</body></html>