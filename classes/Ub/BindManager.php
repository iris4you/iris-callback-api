<?php

class UbBindManager {

	public function getByUserChat($userId, $code) {
		return UbDbUtil::selectOne('SELECT * FROM userbot_bind WHERE id_user = ' . UbDbUtil::intVal($userId) . ' AND code = ' . UbDbUtil::stringVal($code));
	}

	public function getDutyIdByUserChat($code) {
		return (is_array($a = UbDbUtil::selectOne('SELECT * FROM userbot_bind WHERE id_duty > 0 AND code = ' . UbDbUtil::stringVal($code))))?(int)@$a['id_duty']:false;
	}

	public function saveOrUpdate($t) {
		$id_duty = (isset($t['id_duty']) && (int)@$t['id_duty']>0)?(int)@$t['id_duty'] : (int) self::getDutyIdByUserChat($t['code']);
		$sql = 'INSERT INTO userbot_bind SET id_user = ' . UbDbUtil::intVal($t['id_user']) . ', code = ' . UbDbUtil::stringVal($t['code']) . ', id_chat = ' . UbDbUtil::intVal($t['id_chat'])
				. ', id_duty = ' . UbDbUtil::intVal((int)$id_duty)
				. ', title = ' . UbDbUtil::stringVal(@$t['title'])
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