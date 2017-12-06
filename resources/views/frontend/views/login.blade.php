@extends('default')

@section('content')

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif


    @if ($errors->has('login'))
        <div class="alert alert-danger">{!! $errors->first('login', ':message') !!}</div>
    @endif

	<div class="col-md-6 col-md-offset-3 well">
    {!! Form::open(array('url' => 'login')) !!}
		<legend>Login to your account</legend>    	
		<p class="help-block">Welcome back! Please sign in below.</p>
        <div class="form-group">
            {!! Form::label('email', 'Email') !!}
            <div class="input-group">
	            {!! Form::text('email', '', array('placeholder' => 'Enter your email address', 'class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
            </div>
        </div>

        <div class="form-group">
            {!! Form::label('password', 'Password') !!}
            <div class="input-group">
            	{!! Form::password('password', $attributes = array('placeholder' => 'Enter your password', 'class' => 'form-control')) !!}
	            <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
            </div>
        </div>

            {!! Form::submit('Login', array('class' => 'btn btn-primary')) !!}
			<a href="{!!action('password.remind')!!}" class="btn btn-default">Reset Password</a>
	{!! Form::close() !!}
	<hr>
	<p>New client? <a href="{{ URL::to('signup') }}">Sign up here!</a>
    </div>
 
@stop