<?php

class UbBindManager {

	public function getByUserChat($userId, $code) {
		return UbDbUtil::selectOne($this->getSelect('id_user = ' . UbDbUtil::intVal($userId) . ' AND code = ' . UbDbUtil::stringVal($code)));
	}

	public function saveOrUpdate($t) {
		$sql = 'INSERT INTO userbot_bind SET id_user = ' . UbDbUtil::intVal($t['id_user']) . ', code = ' . UbDbUtil::stringVal($t['code']) . ', id_chat = ' . UbDbUtil::intVal($t['id_chat'])
				. ' ON DUPLICATE KEY UPDATE'
				. ' id_chat = VALUES(id_chat)'
		;
		return UbDbUtil::query($sql);
	}

	private function getSelect($cond) {
		return 'SELECT * FROM userbot_bind WHERE ' . $cond;
	}
}