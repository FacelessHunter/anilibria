<?php

function createPasswd($passwd = ''){
	if(empty($passwd)){
		$passwd = genRandStr(8);
	}
	return [$passwd, password_hash($passwd, PASSWORD_DEFAULT)];
}

function genRandStr($length = 10) {
	$str = ''; $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ~!@#$%^&*()_+-=';
	for ($i = 0; $i < $length; $i++) {
		$str .= $chars[random_int(0 ,strlen($chars)-1)];
	}
    return $str;
}

function _mail($email, $subject, $message){
	global $conf;
	$headers  = "MIME-Version: 1.0\r\n";
	$headers .= "Content-type: text/html; charset=utf-8\r\n";
	$headers .= "Content-Transfer-Encoding: base64\r\n";
	$subject  = "=?utf-8?B?".base64_encode($subject)."?=";
	$headers .= "From: {$conf['email_from']} <{$conf['email']}>\r\n";
	mail($email, $subject, rtrim(chunk_split(base64_encode($message))), $headers);
}

function _message($mes, $err = 'ok'){
	$arr = ['err' => $err, 'mes' => $mes];
	echo json_encode($arr);
	die();
}

function half_string($s){
	return substr($s, 0, round(strlen($s)/2));
}

function session_hash($login, $passwd, $rand = '', $time = ''){
	global $conf, $var;
	if(empty($rand)){
		$rand = genRandStr(8);
	}
	if(empty($time)){
		$time = $var['time']+86400;
	}
	return [$rand.hash($conf['hash_algo'], $rand.$var['ip'].$var['user_agent'].$time.$login.half_string($passwd)), $time];
}

function coinhive_proof(){
	global $conf;
	if(empty($_POST['coinhive-captcha-token'])){
		return false;	
	}
	$post_data = [
		'secret' => $conf['coinhive_secret'],
		'token' => $_POST['coinhive-captcha-token'],
		'hashes' => 1024
	];
	$post_context = stream_context_create([
		'http' => [
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($post_data)
		]
	]);
	$url = 'https://api.coinhive.com/token/verify';
	$response = json_decode(file_get_contents($url, false, $post_context));
	if($response && $response->success) {
		return true;
	}
	return false;
}

function _exit(){
	global $db;
	if(session_status() != PHP_SESSION_NONE){
		if(!empty($_SESSION['sess'])){
			$query = $db->prepare("DELETE FROM `session` WHERE `hash` = :hash");
			$query->bindParam(':hash', $_SESSION["sess"], PDO::PARAM_STR);
			$query->execute();
		}
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		session_unset();
		session_destroy();
		header("Location: https://".$_SERVER['SERVER_NAME']);	
	}
}

function login(){
	global $db, $var, $user;
	if($user){
		_message('Already authorized', 'error');
	}
	if(empty($_POST['login']) || empty($_POST['passwd'])){
		_message('Empty post value', 'error');
	}
	if(strlen($_POST['login']) > 20){
		_message('Too long login or email', 'error');
	}
	if(preg_match('/[^0-9A-Za-z]/', $_POST['login'])){
		_message('Wrong login', 'error');
	}
	if(strlen($var['user_agent']) > 256){
		_message('Wrong user agen', 'error');
	}
	$query = $db->prepare("SELECT * FROM `users` WHERE `login` = :login");
	$query->bindValue(':login', $_POST['login'], PDO::PARAM_STR);
	$query->execute();
	if($query->rowCount() == 0){
		_message('Invalid user', 'error');
	}
	$row = $query->fetch();
	if(!password_verify($_POST['passwd'], $row['passwd'])){
		_message('Wrong password', 'error');
	}
	if(password_needs_rehash($row['passwd'], PASSWORD_DEFAULT)){
		$passwd = createPasswd($_POST['passwd']);
		$query = $db->prepare("UPDATE `users` SET `passwd` = :passwd WHERE `id` = :id");
		$query->bindParam(':passwd', $passwd[1], PDO::PARAM_STR);
		$query->bindParam(':id', $row['id'], PDO::PARAM_STR);
		$query->execute();
		$row['passwd'] = $passwd[1];
	}
	$hash = session_hash($row['login'], $row['passwd']);
	$query = $db->prepare("INSERT INTO `session` (`uid`, `hash`, `time`, `ip`, `info`) VALUES (:uid, :hash, :time, :ip, :info)");
	$query->bindParam(':uid', $row['id'], PDO::PARAM_STR);
	$query->bindParam(':hash', $hash[0], PDO::PARAM_STR);
	$query->bindParam(':time', $hash[1], PDO::PARAM_STR);
	$query->bindParam(':ip', $var['ip'], PDO::PARAM_STR);
	$query->bindParam(':info', $var['user_agent'], PDO::PARAM_STR);
	$query->execute();
	$query = $db->prepare("SELECT `id` FROM `session` WHERE `uid` = :uid ORDER BY `time`");
	$query->bindParam(':uid', $row['id'], PDO::PARAM_STR);
	$query->execute();
	if($query->rowCount() > 10){
		$row = $query->fetch();
		$query = $db->prepare("DELETE FROM `session` WHERE `id` = :id");
		$query->bindParam(':id', $row['id'], PDO::PARAM_STR);
		$query->execute();
	}
	$_SESSION['sess'] = $hash[0];
	_message('Success');
}

function password_link(){
	global $conf, $db, $var;
	if(empty($_GET['id']) || empty($_GET['time']) || empty($_GET['hash'])){
		_message('Empty get value', 'error');
	}
	if(!is_numeric($_GET['id']) || !is_numeric($_GET['time'])){
		_message('Wrong id or time', 'error');	
	}
	$query = $db->prepare("SELECT * FROM `users` WHERE `id` = :id");
	$query->bindParam(':id', $_GET['id'], PDO::PARAM_STR);
	$query->execute();
	if($query->rowCount() == 0){
		_message('No such user', 'error');
	}
	$row = $query->fetch();
	$hash = hash($conf['hash_algo'], $var['ip'].$_GET['id'].$_GET['time'].half_string($row['passwd']));
	if($_GET['hash'] != $hash){
		_message('Wrong hash', 'error');
	}
	if($var['time'] > $_GET['time']){
		_message('Invalid link', 'error');
	}
	$passwd = createPasswd();
	$query = $db->prepare("UPDATE `users` SET `passwd` = :passwd WHERE `id` = :id");
	$query->bindValue(':id', $row['id'], PDO::PARAM_STR);
	$query->bindParam(':passwd', $passwd[1], PDO::PARAM_STR);
	$query->execute();
	_mail($row['mail'], "Новый пароль", "Ваш пароль: $passwd[0]");
	_message('Success');
}

function password_recovery(){
	global $conf, $db, $var;
	if(!coinhive_proof()){
		_message('Coinhive captcha error', 'error');
	}
	if(empty($_POST['mail'])){
		_message('Empty post value', 'error');
	}
	if(strlen($_POST['mail']) > 254){
		_message('Too long login or email', 'error');
	}
	if(!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
		_message('Wrong email', 'error');
	}
	$query = $db->prepare("SELECT * FROM `users` WHERE `mail` = :mail");
	$query->bindParam(':mail', $_POST['mail'], PDO::PARAM_STR);
	$query->execute();
	if($query->rowCount() == 0){
		_message('No such user', 'error');
	}
	$row = $query->fetch();
	$time = $var['time']+43200;
	$hash = hash($conf['hash_algo'], $var['ip'].$row['id'].$time.half_string($row['passwd']));
	$link = "http://test.poiuty.com/public/password_link.php?id={$row['id']}&time={$time}&hash={$hash}";
	_mail($row['mail'], "Восстановление пароля", "Запрос отправили с IP $ip<br/>Чтобы восстановить пароль <a href='$link'>перейдите по ссылке</a>.");
	_message('Success');
}

function registration(){
	global $db, $user;
	if($user){
		_message('Already authorized', 'error');
	}
	if(!coinhive_proof()){
		_message('Coinhive captcha error', 'error');
	}
	if(empty($_POST['login']) || empty($_POST['mail'])){
		_message('Empty post value', 'error');
	}
	if(strlen($_POST['login']) > 20 || strlen($_POST['mail']) > 254){
		_message('Too long login or email', 'error');
	}
	if(preg_match('/[^0-9A-Za-z]/', $_POST['login'])){
		_message('Wrong login', 'error');
	}
	if(!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)){
		_message('Wrong email', 'error');
	}
	$query = $db->prepare("SELECT * FROM `users` WHERE `login` = :login OR `mail`= :mail");
	$query->bindValue(':login', $_POST['login'], PDO::PARAM_STR);
	$query->bindParam(':mail', $_POST['mail'], PDO::PARAM_STR);
	$query->execute();
	if($query->rowCount() > 0){
		_message('Already registered', 'error');
	}
	$passwd = createPasswd();
	$query = $db->prepare("INSERT INTO `users` (`login`, `mail`, `passwd`) VALUES (:login, :mail, :passwd)");
	$query->bindValue(':login', $_POST['login'], PDO::PARAM_STR);
	$query->bindParam(':mail', $_POST['mail'], PDO::PARAM_STR);
	$query->bindParam(':passwd', $passwd[1], PDO::PARAM_STR);
	$query->execute();
	_mail($_POST['mail'], "Регистрация", "Вы успешно зарегистрировались на сайте!<br/>Ваш пароль: $passwd[0]");
	_message('Success registration');
}

function auth(){
	global $conf, $db, $var, $user;
	if(!empty($_SESSION['sess'])){
		$query = $db->prepare("SELECT * FROM `session` WHERE `hash` = :hash AND `time` > unix_timestamp(now())");
		$query->bindParam(':hash', $_SESSION['sess'], PDO::PARAM_STR);
		$query->execute();
		if($query->rowCount() != 1){
			_exit();
		}
		$session = $query->fetch();
		$query = $db->prepare("SELECT * FROM `users` WHERE `id` = :id");
		$query->bindParam(':id', $session['uid'], PDO::PARAM_STR);
		$query->execute();
		if($query->rowCount() != 1){
			_exit();
		}
		$row = $query->fetch();
		if($_SESSION['sess'] != session_hash($row['login'], $row['passwd'], substr($session['hash'], 0, 8), $session['time'])[0]){
			_exit();
		}
		if($var['time'] > $session['time']){			
			$hash = session_hash($row['login'], $row['passwd']);
			$query = $db->prepare('UPDATE `session` set `hash` = :hash, `time` = :time WHERE `id` = :id');
			$query->bindParam(':hash', $hash[0], PDO::PARAM_STR);
			$query->bindParam(':time', $hash[1], PDO::PARAM_STR);
			$query->bindParam(':id', $session['id'], PDO::PARAM_STR);
			$query->execute();
			$_SESSION['sess'] = $hash[0];
		}
		$user = ['id' => $row['id'], 'login' => $row['login'], 'passwd' => $row['passwd'], 'mail' => $row['mail']];
	}
}