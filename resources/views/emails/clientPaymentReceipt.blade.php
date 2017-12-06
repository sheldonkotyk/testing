<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
		<h2>HelpYouSponsor Client Receipt</h2>
		<ul>
			<li>Organization: {!! $organization !!}</li>
			<li>Date: {!! $date !!}</li>
			<li>Total Sponsorships: {!! $commitments !!}</li>
			@if( $onetime > 0 )
				<li>Total One Time Donation Charges: {!! $onetime !!}</li>
			@endif
			Subtotal: {!! $commitments + $onetime !!} * $0.25 = ${!! $amount !!}
			<li>Total Amount: ${!! $amount !!}</li>
		</ul>

			<p>Thank you very much for using HelpYouSponsor!</p>

			<p>Contact us at: <a href="mailto:support@helpyousponsor.com">Support@helpyousponsor.com</a></p>
	</body>
</html>