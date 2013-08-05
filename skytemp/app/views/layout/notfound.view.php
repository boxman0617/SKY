<!DOCTYPE html>
<html>
	<head>
		<title>SKY Framework | New Project</title>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta content="<?= Controller::SecurePost(); ?>" name="token" />

		<link href='http://fonts.googleapis.com/css?family=Amatic+SC:400,700|Montserrat+Subrayada' rel='stylesheet' type='text/css'>
		<style>
		html {
		    height: 100%;
		}
		body {
		    height: 100%;
		    margin: 0;
		    background-repeat: no-repeat;
		    background-attachment: fixed;
			background: #d6ecf9;
			background: -moz-linear-gradient(top,  #d6ecf9 0%, #e1e7ea 15%, #afddf7 38%, #d6ecf9 53%, #20261d 55%, #20261d 58%, #1a3013 74%, #5e6041 100%);
			background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#d6ecf9), color-stop(15%,#e1e7ea), color-stop(38%,#afddf7), color-stop(53%,#d6ecf9), color-stop(55%,#20261d), color-stop(58%,#20261d), color-stop(74%,#1a3013), color-stop(100%,#5e6041));
			background: -webkit-linear-gradient(top,  #d6ecf9 0%,#e1e7ea 15%,#afddf7 38%,#d6ecf9 53%,#20261d 55%,#20261d 58%,#1a3013 74%,#5e6041 100%);
			background: -o-linear-gradient(top,  #d6ecf9 0%,#e1e7ea 15%,#afddf7 38%,#d6ecf9 53%,#20261d 55%,#20261d 58%,#1a3013 74%,#5e6041 100%);
			background: -ms-linear-gradient(top,  #d6ecf9 0%,#e1e7ea 15%,#afddf7 38%,#d6ecf9 53%,#20261d 55%,#20261d 58%,#1a3013 74%,#5e6041 100%);
			background: linear-gradient(to bottom,  #d6ecf9 0%,#e1e7ea 15%,#afddf7 38%,#d6ecf9 53%,#20261d 55%,#20261d 58%,#1a3013 74%,#5e6041 100%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#d6ecf9', endColorstr='#5e6041',GradientType=0 );
		}
		#error {
			width: 300px;
			margin: 0 auto;
		}
		#error h1 {
			margin: 0;
			font-family: 'Montserrat Subrayada', sans-serif;
			font-weight: bold;
			font-size: 150px;
		}
		#error h3 {
			margin: 0;
			font-family: 'Amatic SC', cursive;
			font-size: 70px;
			display: block;
			position: relative;
			top: -48px;
		}
		</style>
	</head>

	<body>
		<? Controller::RenderSubView(); ?>
	</body>
</html>