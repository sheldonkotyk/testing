<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		{!! $body !!}
		
		<br><br><br><br>
		<p><small>If you would rather not receive these emails you may opt-out by clicking here: <a href="{{ URL::to('opt_out', array($to, $id)) }}">{{ URL::to('opt_out', array($to, $id)) }}</a>.</small></p>
		<p><small>*Note: Opting out removes you from all emails sent from our system.</small></p>
	</body>
</html>