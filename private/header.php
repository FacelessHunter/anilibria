<?php
if(!empty($_SESSION['csrf'])){
	$csrf_token = json_encode(csrf_token());
}else{
	$csrf_token = '';
}

function headerAds(){
	global $cache, $var; $result = []; $left = 0;
	$img = urlCDN('/img/29.png');
	$result['ads'] = false;

	//$data[] = ['img' => 'ragnarok1', 'left' => '0px', 'url' => '/ro'];
	//$data[] = ['img' => 'ragnarok1', 'left' => '0px', 'url' => '/ro1'];
	$data[] = ['img' => 'bs', 'left' => '-195px', 'url' => '/bs'];
	$data[] = ['img' => 'storm', 'left' => '0px', 'url' => '/storm'];
	$data[] = ['img' => 'cro', 'left' => '0px', 'url' => '/cro'];
	$data[] = ['img' => 'rise', 'left' => '0px', 'url' => '/rise'];
	$data[] = ['img' => 'ironsight1', 'left' => '0px', 'url' => '/ironsight'];
	//$data[] = ['img' => 'bravo1', 'left' => '0px', 'url' => '/bravo1'];
	//$data[] = ['img' => 'bravo2', 'left' => '0px', 'url' => '/bravo2'];

	if(checkADS()){
		//$cday = date('j', $var['time']);
		//$key = $cache->get('adsCurrent');
		//$day = $cache->get('adsCurrentDay');
		//if($key === false || $day === false || $day != $cday){
		//	if($key === false){
		//		$key = 0;
		//	}else{
		//		$key++;
		//	}
		//	if($key > count($data)-1){
		//		$key = 0;
		//	}
		//	//$key = random_int(0, count($data)-1);
		//	$cache->set('adsCurrent', $key, 172800);
		//	$cache->set('adsCurrentDay', $cday, 172800);
		//}
/*
		$key = 0;
		$ads = $data["$key"];
		
		$img = urlCDN('/img/other/a/'.$ads['img'].'.jpg');
		$left = $ads['left'];
		$result['url'] = $ads['url'];
		$result['ads'] = true;
		*/
	}
	/*Удалить когда голосование закончится*/
	/*$img = urlCDN('/img/29.png');
	$result['url'] = '/season/2020winter.html';
	$result['ads'] = true;*/
	/*------------------------------------*/
	
	$css = getTemplate('header');
	$css = str_replace('{img}', $img, $css);
	$css = str_replace('{left}', $left, $css);
	$result['css'] = $css;
	return $result;
}

$xcss = headerAds();
?>

<!DOCTYPE html>
<html prefix="og: http://ogp.me/ns#">
	<head>
		<!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-137180052-1"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());
		  gtag('config', 'UA-137180052-1');
		</script>		
		<?php echo $var['og']; ?>
		<title><?php echo $var['title']; ?></title>
		<meta charset="UTF-8">
		<meta name="description" content="<?php echo strip_tags($var['description']); ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo urlCDN(fileTime('/css/bootstrap.min.css'));?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo urlCDN(fileTime('/css/main.css'));?>" />
		<style><?php echo $xcss['css']; ?></style>
	</head>
	<body>
		<input type="hidden" id="csrf_token" value='<?php echo $csrf_token; ?>'>
		<!-- AdguardTeam and AdBlock (dimisa) , please block any ads you want. -->
		<div id="headercontent"></div>
		<div class="link">
		<?php
			if($xcss['ads']){
				echo '<a href="'.$xcss['url'].'" data-toggle="tooltip" data-placement="right" title="Вы можете отключить рекламу в личном кабинете"></a>';
			}
		?>
		</div>
		<div class="header">
			<?php
				if($xcss['ads']){
					echo "<div class='headercontent'></div>";
				}
			?>
		</div>
		<div class="main">
			<div class="content">
				<div class="contentmenu">
					<ul class="main-navigation">
						<li><a id="activelink0" href="/">ГЛАВНАЯ</a></li>
						<li><a id="activelink1" href="/pages/catalog.php">РЕЛИЗЫ</a></li>
						<li><a id="activelink2" href="/pages/schedule.php">РАСПИСАНИЕ</a></li>
						<li><a data-random-release id="activelink3" href="/public/random.php">СЛУЧАЙНОЕ</a></li>
						<li><a id="activelink4" href="/pages/app.php">ПРИЛОЖЕНИЕ</a></li>
						<li><a id="activelink5" href="/pages/team.php">КОМАНДА</a></li>
						<li><a id="activelink6" href="/pages/donate.php">ПОДДЕРЖАТЬ ПРОЕКТ</a></li>
					</ul>
				</div>





<!--<div class="alert alert-warning" role="alert" style="margin-top: 10px; margin-bottom: 0px; font-size: 12.2pt; color: #000000;">
	Сайт заблокирован в России. Поддержите проект подпиской на <a href="https://t.me/anilibria_tv" target="_blank">телеграм</a>, <a href="https://vk.com/anilibria" target="_blank">вконтакте</a> или <a href="https://www.anilibria.tv/pages/donate.php" target="_blank">донатом</a>.<br/> Спасибо, что вы с нами!
</div>-->
