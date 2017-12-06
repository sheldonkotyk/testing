@extends('frontend.default')

@section('content')
    @if ($errors->has('login'))
        <div class="alert alert-danger">{!! $errors->first('login', ':message') !!}</div>
    @endif
    

    
  <div class="col-md-6 col-md-offset-3 ">
    <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">Forgot My Username</h3></div>
        <div class="panel-body">
    {!! Form::open(array('url' => 'frontend/forgot_username/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}
        <p class="help-block">Enter your email address and click "send me my username". <br> Then you will receive an email from us with your username.</p>
        
        <div class="form-group">
            {!! Form::label('email', 'Email Address') !!}
            <div class="input-group">
                {!! Form::text('email', '', array('placeholder' => 'Enter your Email Address', 'class' => 'form-control')) !!}
                
                <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
            </div>
            <strong>{!! $errors->first('email', '<p class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> :message </p>') !!}</strong>
        </div>
        <div class="form-group">
            {!! Form::submit('Send me my Username', array('class' => 'btn btn-primary form-control')) !!}  
        </div>

    {!! Form::close() !!}
    </div>
    </div>
    </div>
 
@stop
