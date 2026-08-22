<?php
/* Thai translation by jaideejung007, https://xiunothai.ml/ */
return array(
	'installed_tips' => 'คุณได้ติดตั้งไปแล้ว หากต้องการติดตั้งใหม่ กรุณาลบไฟล์ conf/conf.php ออกก่อน',
	'please_set_conf_file_writable' => 'กรุณากำหนดสิทธิ์ไฟล์ให้สามารถเขียนไฟล์ conf/conf.php ได้',
	'evn_not_support_php_mysql' => 'สภาพแวดล้อม PHP ที่ใช้ในปัจจุบัน ยังไม่รองรับ mysql และ pdo_mysql การติดตั้งจะไม่สามารถดำเนินการต่อได้',
	'dbhost_is_empty' => 'โฮสต์ฐานข้อมูลจะต้องไม่ว่างเปล่า',
	'dbname_is_empty' => 'ชื่อฐานข้อมูลจะต้องไม่ว่างเปล่า',
	'dbuser_is_empty' => 'ชื่อผู้ใช้ฐานข้อมูลจะต้องไม่ว่างเปล่า',
	'adminuser_is_empty' => 'ชื่อแอดมินจะต้องไม่ว่างเปล่า',
	'adminpass_is_empty' => 'รหัสผ่านแอดมินจะต้องไม่ว่างเปล่า',
	'conguralation_installed' => 'ยินดีด้วยจ้า ติดตั้งเสร็จแล้ว เพื่อความปลอดภัย กรุณาลบโฟลเดอร์ install ออกก่อนใช้งาน',
	
	'step_1_title' => '1. ตรวจสอบสภาพแวดล้อม',
	'runtime_env_check' => 'ตรวจสอบสภาพแวดล้อมรันไทม์',
	'required' => 'ต้องการ',
	'current' => 'ปัจจุบัน',
	'check_result' => 'ผลการตรวจ',
	'passed' => 'ผ่าน',
	'not_passed' => 'ไม่ผ่าน',
	'not_the_best' => 'สภาพแวดล้อมไม่เหมาะสม',
	'dir_writable_check' => 'สิทธิการเข้าถึงไฟล์และโฟลเดอร์',
	'writable' => 'เขียนได้',
	'unwritable' => 'เขียนไม่ได้',
	'check_again' => 'ตรวจสอบอีกครั้ง',
	'os' => 'OS',
	'unix_like' => 'UNIX Like',
	'php_version' => 'เวอร์ชัน PHP',
	
	'step_2_title' => '2. ตั้งค่าฐานข้อมูล',
	'db_type' => 'ประเภทฐานข้อมูล',
	'db_engine' => 'โปรแกรมฐานข้อมูล',
	'db_host' => 'โฮสต์ฐานข้อมูล',
	'db_name' => 'ชื่อฐานข้อมูล',
	'db_user' => 'ชื่อผู้ใช้ฐานข้อมูล',
	'db_pass' => 'รหัสผ่านฐานข้อมูล',
	'step_3_title' => '3. ข้อมูลแอดมิน',
	'admin_email' => 'อีเมลแอดมิน',
	'admin_username' => 'ชื่อผู้ใช้แอดมิน',
	'admin_pw' => 'รหัสผ่านแอดมิน',
	'installing_about_moment' => 'กำลังติดตั้ง ใช้เวลาประมาณ 1 นาทีหรือมากกว่านั้น',
	'license_title' => 'ข้อตกลง XIUNO XW 1.1.1',
	'license_content' => 'ขอขอบคุณที่เลือกใช้งาน XIUNO XW 1.1.1 (fork ของ Xiuno BBS 4.0.4) รองรับ PHP 8.0 - 8.4 อัปเกรดระบบ SMTP และระบบแคช',
	'license_date' => 'อัปเดตล่าสุด: 21 พฤษภาคม 2026',
	'agree_license_to_continue' => 'ยอมรับข้อตกลงและดำเนินการติดตั้งต่อ',
	'install_title' => 'ตัวช่วยติดตั้ง XIUNO XW 1.1.1',
	'install_guide' => 'ตัวช่วยติดตั้ง',

	
	'function_check' => 'ตรวจสอบฟังก์ชันที่ต้องการ',
	'supported' => 'รองรับ',
	'not_supported' => 'ไม่รองรับ',
	'function_glob_not_exists' => 'จำเป็นต้องมีการติดตั้งส่วนขยายเพิ่มเติม กรุณาตั้งค่าในไฟล์ php.ini ให้ disabled_functions = ; ยกเว้นข้อจำกัดในฟังก์ชั่นนี้',
	'function_gzcompress_not_exists' => 'จำเป็นต้องมีการติดตั้งส่วนขยายเพิ่มเติม สำหรับเซิร์ฟเวอร์ Linux ให้เพิ่ม compile argument: --with-zlib, ส่วนเซิร์ฟเวอร์ Windows กรุณาตั้งค่า php.ini ให้เปิด extension=php_zlib.dll',
	'function_mb_substr_not_exists' => 'จำเป็นสำหรับระบบ กรุณาเปิดใช้งานส่วนขยาย mbstring ใน php.ini (ลบ ; หน้า extension=mbstring) จากนั้นรีสตาร์ทเว็บเซิร์ฟเวอร์',
	
	// hook lang_th_th_bbs_install.php
);

?>