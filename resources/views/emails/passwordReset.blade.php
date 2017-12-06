<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>Password Reset for : {!!$name!!} </h2>
		<ul>
			<li>Your New Password is : {!!$password!!} </li>
			<li><a href="{!!$login_link!!}">Click here to login</a></li>
			<li><strong>Be sure to login with your username. <a href="{!!$forgot_username_link!!}">If you have forgotten your username click here.</a></strong></li>
		</ul>

			<p>Note: Once logged in, you may change this random password to something easy for you to remember by clicking on "Update My Info"</p>

		Thank you!
		<br>
		<a href="{!!$website!!}">{!!$organization!!}</a>
		<br>
	</body>
</html>