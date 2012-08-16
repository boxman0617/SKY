<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>SKY Framework</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	<meta content="<?= Controller::SecurePost(); ?>" name="token" />
	<? if(isset($css)): ?>
		<? foreach($css as $c): ?>
			<link href="<?= $c; ?>" rel="stylesheet" type="text/css">
		<? endforeach; ?>
	<? endif; ?>
</head>

<body>
	<? Controller::yield(); ?>
</body>
</html>