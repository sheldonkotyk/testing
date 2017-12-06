<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Error Notification</h2>
		<p>
			<?php var_dump($exception) ?>
		</p>
		
		<h3>URL</h3>
		<p>
			{!! $url !!}
		</p>
		
		<h3>Inputs</h3>
		<p>
			<?php var_dump($inputs); ?>
		</p>
		
		<h3>Client Id</h3>
		<p> 
			{!! $client !!}
		</p>
	</body>
</html>