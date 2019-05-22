<?php

class UbDataManager {

	function get($userId) {
		return UbDbUtil::selectOne($this->getSelect('id_user = ' . UbDbUtil::intVal($userId)));
	}

	private function getSelect($cond) {
		return 'SELECT * FROM userbot_data WHERE ' . $cond;
	}

	public function getByIdSecret($userId, $secret) {
		return UbDbUtil::selectOne($this->getSelect('id_user = ' . UbDbUtil::intVal($userId) . ' AND secret = ' . UbDbUtil::stringVal($secret)));
	}
}