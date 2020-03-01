ALTER TABLE `userbot_data` ADD `a_add` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `id_user` ,
ADD `a_del` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `a_add`;