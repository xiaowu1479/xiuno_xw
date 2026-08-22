<?php

return array(
	'installed_tips' => 'Форум уже установлен, если вы хотите переустановить, удалите conf/conf.php',
	'please_set_conf_file_writable' => 'Установите права чтения/запси для conf/conf.php !',
	'evn_not_support_php_mysql' => 'Текущая версия PHP,mysql и pdo_mysql driver не соответствует минимальным системным требованиям, не могу установить',
	'dbhost_is_empty' => 'Введите имя сервера',
	'dbname_is_empty' => 'Введите имя базы данных',
	'dbuser_is_empty' => 'Укажите пользователя БД',
	'adminuser_is_empty' => 'Не может быть пустым',
	'adminpass_is_empty' => 'Не может быть пустым',
	'conguralation_installed' => 'Поздравляем, вы установили форум, не забудьте удалить папку install в целях вашей безопасности.',
	
	'step_1_title' => '1. Системные требования',
	'runtime_env_check' => 'Системные требования',
	'required' => 'Требуется',
	'current' => 'Ваш сервер',
	'check_result' => 'Результат',
	'passed' => 'Соотвествует',
	'not_passed' => 'Не соответсвует',
	'not_the_best' => 'Не соответсвует системным требованиям',
	'dir_writable_check' => 'Проверка прав доступа',
	'writable' => 'Чтение/запись',
	'unwritable' => 'Чтение',
	'check_again' => 'Проверить снова',
	'os' => 'OS',
	'unix_like' => 'UNIX',
	'php_version' => 'Версия PHP',
	
	'step_2_title' => '2. База данных',
	'db_type' => 'Тип',
	'db_engine' => 'Движок',
	'db_host' => 'Сервер',
	'db_name' => 'Название',
	'db_user' => 'Пользователь',
	'db_pass' => 'Пароль',
	'step_3_title' => '3. Администратор',
	'admin_email' => 'E-mail',
	'admin_username' => 'Логин',
	'admin_pw' => 'Пароль',
	'installing_about_moment' => 'Установка, ожидайте...',
	'license_title' => 'XIUNO XW 1.1.1 Лицензионное соглашение',
	'license_content' => 'Thank you to choose XIUNO XW 1.1.1, a fork of Xiuno BBS 4.0.4, fully compatible with PHP 8.0 - 8.4, SMTP mail system upgraded, cache mechanism enhanced.',
	'license_date' => 'Последнее обновление: August 21, 2026',
	'agree_license_to_continue' => 'Принять лицензию и продолжить',
	'install_title' => 'XIUNO XW 1.1.1 Мастер установки',
	'install_guide' => 'Мастер установки',

	
	'function_check' => 'Проверка необходимых функций',
	'supported' => 'Поддерживается',
	'not_supported' => 'Не поддерживается',
	'function_glob_not_exists' => 'Plugin install dependent on it, please setting php.ini, set disabled_functions = ; Lifting restrictions on this function',
	'function_gzcompress_not_exists' => 'Plugin install dependent on it, on Linux server, add compile argument: --with-zlib, on Windows Server, please setting php.ini open extension=php_zlib.dll',
	'function_mb_substr_not_exists' => 'Требуется системой. Включите расширение mbstring в php.ini (удалите ; перед extension=mbstring), затем перезапустите веб-сервер',
	
	// hook lang_en_us_bbs_install.php
);

?>
