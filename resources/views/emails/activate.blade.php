<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h1>Welcome to HelpYouSponsor!</h1>
		<p>Thank you for becoming part of HelpYouSponsor. We hope this is the next step to helping you create a very successful sponsorship program. If at any time you need help or have questions, please contact us at <a href="mailto:support@helpyousponsor.com">support@helpyousponsor.com</a> Sending us an email is the best way to get in contact with someone knowledgeable who can provide expert assistance. In an effort to keep our costs low so we can continue to provide this software at a very reasonable cost we do not offer telephone support.</p>
		<p>We also have online documentation which details how to use the various parts of the software. Keep in mind that this is a brand new release of the software so the new documentation is still being developed. If you have questions that aren't answered in the documentation please contact us.</p>
		<p>Access the online documentation here: <a href="http://help.helpyousponsor.com/">http://help.helpyousponsor.com/</a></p>
		
		<h2>Account Activation</h2>
		<p>To activate your account click on this link or copy and paste it into your browser: <a href="{{ URL::to('activate_user', array($id, $activationCode)) }}">{{ URL::to('activate_user', array($id, $activationCode)) }}</a>.</p>
	</body>
</html>