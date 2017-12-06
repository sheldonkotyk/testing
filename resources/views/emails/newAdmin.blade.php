<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Admin Account Activation</h2>
		<p>Here is your login information:</p>
		<ul>
			<li>User: {!! $email !!}</li>
			<li>Password: {!! $password !!}</li>
		</ul>

		<p>
			To activate your account click on this link or copy and paste it into your browser: <a href="{{ URL::to('activate_user', array($id, $activationCode)) }}">{{ URL::to('activate_user', array($id, $activationCode)) }}</a>.
		</p>
	</body>
</html>