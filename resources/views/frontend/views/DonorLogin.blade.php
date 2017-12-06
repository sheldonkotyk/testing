@extends('frontend.default')

@section('content')
    @if ($errors->has('login'))
        <div class="alert alert-danger">{!! $errors->first('login', ':message') !!}</div>
    @endif
    
<div class="col-md-6 col-md-offset-3 ">
	<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">Login to your Donor account</h3></div>
        <div class="panel-body">
         {!! Form::open(array('url' => 'frontend/login/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}

		<p class="help-block">Welcome back! Please sign in below.</p>
        
        <div class="form-group">
            {!! Form::label('username', 'Username') !!}
            {!! Form::text('username', '', array('placeholder' => 'Enter your Username', 'class' => 'form-control')) !!}
            {!! $errors->first('username', '<p class="text-danger">:message</p>') !!}
        </div>

        <div class="form-group">
            {!! Form::label('password', 'Password') !!}
            {!! Form::password('password', $attributes = array('placeholder' => 'Enter your password', 'class' => 'form-control')) !!}
             {!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
        </div>
        <div class="form-group">
            {!! Form::submit('Login', array('class' => 'btn btn-primary form-control')) !!}<br><br>
			<a href="{{ URL::to('frontend/reset_password/'.$client_id.'/'.$program_id.'/'.$session_id) }}" class="btn btn-default btn-sm pull-right">Forgot Password</a>
            <a href="{{ URL::to('frontend/forgot_username/'.$client_id.'/'.$program_id.'/'.$session_id) }}" class="btn btn-default btn-sm pull-right">Forgot Username</a>
        </div>

	{!! Form::close() !!}
    </div>
 
    </div>
    </div>
@stop