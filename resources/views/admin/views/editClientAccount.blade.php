@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
	<script>
	
	</script>
@stop

@section('content')
	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Account Info<small> <span class="icon ion-clipboard"></span> Edit Account Info</small></h2>

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

                                        
		<div id="" class="panel panel-default">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-clipboard"></i></div>
                <div class="panel-actions">
                    <div class="badge"></div>
                </div>
               
                <h3 class="panel-title">Edit Account Info</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">
            <div class="col-md-12">
		{!! Form::model($client) !!}
			<div class="form-group">
				{!! Form::label('organization', 'Organization Name') !!}
				{!! Form::text('organization', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('organization', '<p class="text-danger">:message</p>') !!}
			</div>
			
			<div class="form-group">
				{!! Form::label('website', 'Website') !!}
				{!! Form::text('website', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('website', '<p class="text-danger">:message</p>') !!}
			</div>
			
			<div class="form-group">
				{!! Form::label('email', 'Email') !!}
				{!! Form::text('email', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
				<p class="help-text">This email is used for contacting you regarding any account related issues</p>
			</div>

			<hr>
			
			@if($donation->checkUseCC())
				<p><h3>Using <strong>{!!ucfirst($donation->checkUseCC())!!}</strong> for Credit Card processing.</h3></p>
			@else
				<p><h3>No Credit Card processing is configured.</h3></p>
			@endif

			@if (!empty($stripe_gateway))
				<div class="form-group">
					@if ($stripe_gateway['gateway'] == 'Stripe')
						@if (isset($stripe_gateway['settings']['StripeApiKey']))
							<?php $value1 = $stripe_gateway['settings']['StripeApiKey']; ?>
						@endif

						{!! Form::label('stripe', 'Stripe Secret Key') !!}
						{!! Form::text('stripe', $value1, $attributes = array('placeholder' => 'Enter your Stripe Secret Key', 'class' => 'form-control')) !!}
						<p class="help-text">You can enter the Test Secret Key for testing. You must enter the Live Secret Key before going live.</p>
						{!! Form::hidden('stripe_gateway_id', $stripe_gateway['id']) !!}
					@endif
				</div>
			@else
			<div class="form-group">
					{!! Form::label('stripe', 'Stripe Secret Key') !!}
					{!! Form::text('stripe', '', $attributes = array('placeholder' => 'Enter your Stripe Secret Key', 'class' => 'form-control')) !!}
					<p class="help-text">You can enter the Test Secret Key for testing. You must enter the Live Secret Key before going live.</p>
			</div>
			@endif
				
			@if(!empty($authorize_gateway))
					@if ($authorize_gateway['gateway'] == 'AuthorizeNet')
						@if (isset($authorize_gateway['settings']['ApiLoginId']))
							<?php $key1 = $authorize_gateway['settings']['ApiLoginId']; ?>
						@endif
						@if (isset($authorize_gateway['settings']['TransactionKey']))
							<?php $key2 = $authorize_gateway['settings']['TransactionKey']; ?>
						@endif

					{!! Form::label('login_api_key', 'Authorize.Net Api Login Key') !!}
					{!! Form::text('login_api_key', $key1, $attributes = array('placeholder' => 'Enter your Authorize.Net API Login ID', 'class' => 'form-control')) !!}
					<p class="help-text">You can enter your sandbox, or production id here.</p>
					
					{!! Form::label('transaction_api_key', 'Authorize.Net Transaction Key') !!}
					{!! Form::text('transaction_api_key', $key2, $attributes = array('placeholder' => 'Enter your Authorize.Net Transaction Key', 'class' => 'form-control')) !!}
					<p class="help-text">You can enter your sandbox, or production key here.</p>
					{!! Form::hidden('authorize_gateway_id', $authorize_gateway['id']) !!}
					@endif
					{!! Form::checkbox('arb_enabled', '1') !!}
					{!! Form::label('arb_enabled', 'Enable Authorize ARB as a payment method? (Automated Recurring Billing) ') !!} <a href="http://help.helpyousponsor.com/read/authorize.net_arb_setup"> ARB Setup Info</a>
			@else
				<div class="form-group">
					{!! Form::label('login_api_key', 'Authorize.Net Api Login ID') !!}
					{!! Form::text('login_api_key', '', $attributes = array('placeholder' => 'Enter your Authorize.Net API Login ID', 'class' => 'form-control')) !!}
					<p class="help-text">You can enter your sandbox, or production id here.</p>
				</div>
				<div class="form-group">
					{!! Form::label('transaction_api_key', 'Authorize.Net Transaction Key') !!}
					{!! Form::text('transaction_api_key', '', $attributes = array('placeholder' => 'Enter your Authorize.Net Transaction Key', 'class' => 'form-control')) !!}
					<p class="help-text">You can enter your sandbox, or production key here.</p>
				</div>
			@endif

			<hr>

			<p><h3>Box.com Integration</h3></p>
			<p class="help-text">These fields are required if you wish to store full size images with Box.com</p>

			<p class="help-text">Go to <a href="http://help.helpyousponsor.com/read/box.com_image_storage_setup">help.helpyousponsor.com</a> for more information on setting this up.</p>
			<p class="help-text">Don't forget to contact Box.com to <a href="http://help.helpyousponsor.com/read/box.com_image_storage_setup">setup your CORS support</a>.</p>
			<div class="form-group">
				{!! Form::label('box_client_id', 'Box.Com Client id') !!}
				{!! Form::text('box_client_id', $value = null, $attributes = array('placeholder' => 'Enter your Box.com client id', 'class' => 'form-control')) !!}
				<p class="help-text">Enter your box.com client id here.</p>
			</div>
			<div class="form-group">
				{!! Form::label('box_client_secret', 'Box.Com Client Secret') !!}
				{!! Form::text('box_client_secret', $value= null, $attributes = array('placeholder' => 'Enter your Box.com client secret', 'class' => 'form-control')) !!}
				<p class="help-text">Enter your box.com client secret here.</p>
			</div>
			</div>
			<div class="col-md-6">
			{!! Form::submit('Save', array('class' => 'btn btn-primary form-control ')) !!}
			</div>

		{!! Form::close() !!}
	</div>
</div>

@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$('.hysTextarea').redactor();
	});
	</script>
@stop