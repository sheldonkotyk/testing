@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

	<h2>Add Payment</h2>
	@if( $details['frequency']=='Monthly')
		<p class="lead">This payment will be applied to <b>{!! $details['designation_name'] !!}</b>. The amount is {!! $details['currency_symbol'] !!}{{ $details['amount'] }} paid {!! $details['frequency'] !!} by {!! $details['method'] !!}.</p>
	@else
		<p class="lead">This payment will be applied to <b>{!! $details['designation_name'] !!}</b>. The amount is {!! $details['currency_symbol'] !!}{{ $details['amount'] }} Monthly ({!!$details['currency_symbol'].$details['frequency_total']!!} Paid {!! $details['frequency'] !!}) by {!! $details['method'] !!}.</p>
	@endif
	<div class="reverse-well">
	{!! Form::model($commitment) !!}
		<div class="form-group">
			{!! Form::label('created_at', 'Date') !!}
			{!! Form::text('created_at', '', array('class' => 'form-control', 'placeholder' => 'Format date YYYY-MM-DD')) !!}
			<p class="help-text">Leave blank for today's date.</p>
		</div>

		<div class="form-group">
			{!! Form::label('amount', 'Payment Amount') !!}
			{!! Form::text('amount', sprintf("%01.2f", $details['frequency_total']), array('class' => 'form-control', 'placeholder' => 'Format number as 10.00')) !!}
			<p class="help-block">Amount must be formatted to two decimal places like 10.00 not 10.</p>
		</div>
		
		<div class="form-group">
			{!! Form::label('method', 'Method') !!}
			{!! Form::select('method', $dntns->getMethods(), null, array('class' => 'form-control')) !!}
		</div>
		<div id="cc-warning" class="form-group"></div>
		<div id="cc-form" class="form-group"></div>
		
		<div class="form-group">
			{!! Form::label('result', 'Result (note)') !!}
			{!! Form::textarea('result', $value = null, array('class' => 'form-control hysTextarea')) !!}
		</div>
		
		{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
		<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
	{!! Form::close() !!}
	</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$('.hysTextarea').redactor();
		$( "#created_at" ).datepicker({ dateFormat: "yy-mm-dd" });
		
		$('#method').change(function() {
			var selected = $(this).val();
			var useCC =  '{!! $useCC !!}';
			var donorCardActive = '{!! $donorCardActive !!}';
			
			if ( selected == 3 ) {
				$('div#cc-warning').html('<span class="label label-info">Note: This will Charge the Donor\'s Credit Card.</span>');
				if ( useCC == true ) {
					if ( ! donorCardActive ) {
						$('div#cc-form').html('<label for="firstName">First Name</label><input class="form-control" type="text" name="firstName" placeholder="First Name"><label for="lastName">Last Name</label><input class="form-control" type="text" name="lastName" placeholder="Last Name"><label for="number">Credit Card Number</label><input class="form-control" type="text" name="number" placeholder="Enter Credit Card Number"><label for="cvc">CVV</label><input class="form-control" type="text" name="cvv" placeholder="CVV"><label for="expiryMonth">Expiration Month</label><input class="form-control" type="text" name="expiryMonth" placeholder="MM"><label for="expiryYear">Expiration Year</label><input class="form-control" type="text" name="expiryYear" placeholder="YYYY">');
					}
				}
			} else {
				$('div#cc-warning').html('');
				$('div#cc-form').html('');
			}
		});

	});
	</script>
@stop