@extends('default')

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

	<div class="col-md-6 col-md-offset-3 well">
    {!! Form::open() !!}
		<legend>Create your account</legend>    	
		<p class="help-block">Welcome to HelpYouSponsor!</p>
		
        <div class="form-group">
            {!! Form::label('first_name', 'First Name') !!}
            <div class="input-group">
	            {!! Form::text('first_name', '', array('class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
            </div>
            {!! $errors->first('first_name', '<p class="text-danger">:message</p>') !!}
        </div>
        
        <div class="form-group">
            {!! Form::label('last_name', 'Last Name') !!}
            <div class="input-group">
	            {!! Form::text('last_name', '', array('class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
            </div>
        </div>
        
        <div class="form-group">
            {!! Form::label('organization', 'Organization Name') !!}
            <div class="input-group">
	            {!! Form::text('organization', '', array('class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-tower"></span></span>
            </div>
            {!! $errors->first('organization', '<p class="text-danger">:message</p>') !!}
        </div>
        
        <div class="form-group">
            {!! Form::label('website', 'Website Address') !!}
            <div class="input-group">
	            {!! Form::text('website', '', array('placeholder' => 'Format: http://helpyousponsor.com', 'class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-link"></span></span>
            </div>
            {!! $errors->first('website', '<p class="text-danger">:message</p>') !!}
        </div>
		
        <div class="form-group">
            {!! Form::label('email', 'Email') !!}
            <div class="input-group">
	            {!! Form::text('email', '', array('placeholder' => 'Enter your email address', 'class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
            </div>
            {!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
        </div>

        <div class="form-group">
            {!! Form::label('password', 'Password') !!}
            <div class="input-group">
            	{!! Form::password('password', $attributes = array('placeholder' => 'Enter your password', 'class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
            </div>
            {!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
        </div>
        
        <div class="form-group">
        	{!! Form::label('number', 'Credit Card Number') !!}
        	<div class="input-group">
        		{!! Form::text('number', '', array('placeholder' => 'Enter credit card number', 'class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-credit-card"></span></span>
        	</div>
        	{!! $errors->first('number', '<p class="textj-danger">:message</p>') !!}
        </div>
        
		<div class="form-group">
			<div class="row">
				<div class="col-xs-2">
					{!! Form::label('expiryMonth', 'Month') !!}
					{!! Form::text('expiryMonth', '', array('placeholder' => 'MM', 'class' => 'form-control')) !!}
				</div>
				<div class="col-xs-2">
					{!! Form::label('expiryYear', 'Year') !!}
					{!! Form::text('expiryYear', '', array('placeholder' => 'YYYY', 'class' => 'form-control')) !!}
				</div>
				<div class="col-xs-2">
					{!! Form::label('cvc', 'CVV') !!}
					{!! Form::text('cvc', '', array('placeholder' => 'CVV', 'class' => 'form-control')) !!}
				</div>
			</div>
				{!! $errors->first('expiryMonth', '<p class="text-danger">:message</p>') !!}
				{!! $errors->first('expiryYear', '<p class="text-danger">:message</p>') !!}
				{!! $errors->first('cvc', '<p class="text-danger">:message</p>') !!}
		</div>

            {!! Form::submit('Create Account', array('class' => 'btn btn-primary')) !!}
	{!! Form::close() !!}
    </div>
@stop