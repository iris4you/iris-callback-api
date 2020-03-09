<?php

class UbBindManager {

	public function getByUserChat($userId, $code) {
		return UbDbUtil::selectOne($this->getSelect('id_user = ' . UbDbUtil::intVal($userId) . ' AND code = ' . UbDbUtil::stringVal($code)));
	}

	public function getDutyIdByUserChat($code) {
		$id_duty = (is_array($a = UbDbUtil::selectOne($this->getSelect('id_duty > 0 AND code = ' . UbDbUtil::stringVal($code)))))?(int)@$a['id_duty']:false;
		return $id_duty;
	}

	public function saveOrUpdate($t) {
		$id_duty = (isset($t['id_duty']) && (int)@$t['id_duty']>0)?(int)@$t['id_duty']:false;
		if ($id_duty && (is_numeric($id_duty)) && (int)$id_duty == (int)@$t['id_user']) {
		UbDbUtil::query("UPDATE `userbot_bind` SET `id_duty` = '". UbDbUtil::intVal($t['duty'])."' WHERE `code` = '$t[code]';"); } else {
		$id_duty = (int) self::getDutyIdByUserChat($t['code']); }
		$sql = 'INSERT INTO userbot_bind SET id_user = ' . UbDbUtil::intVal($t['id_user']) . ', code = ' . UbDbUtil::stringVal($t['code']) . ', id_chat = ' . UbDbUtil::intVal($t['id_chat'])
				. ', id_duty = ' . UbDbUtil::intVal((int)$id_duty)
				. ', title = ' . UbDbUtil::stringVal(@$t['title'])
				. ', link = ' . UbDbUtil::stringVal(@$t['link'])
				. ' ON DUPLICATE KEY UPDATE'
				. ' id_chat = VALUES(id_chat)'
				. ', id_duty = VALUES(id_duty)'
				. ', title = VALUES(title)'
		;

		return UbDbUtil::query($sql);
	}

	private function getSelect($cond) {
		return 'SELECT * FROM userbot_bind WHERE ' . $cond;
	}
}
