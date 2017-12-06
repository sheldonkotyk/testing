@extends('frontend.default')

@section('content')
    @if ($errors->has('login'))
        <div class="alert alert-danger">{!! $errors->first('login', ':message') !!}</div>
    @endif
    

	<div class="col-md-6 col-md-offset-3 ">
    <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">Reset My Password</h3></div>
        <div class="panel-body">
    {!! Form::open(array('url' => 'frontend/reset_password/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}
		<p class="help-block">Enter your username <strong>or</strong> email address and click "Send Temporary Password". <br> Then you will receive an email from us with a temporary password.</p>
        <p class="help-block">You may then login and click on "Update my info" to set a new password of your choosing.</p>
        
        <div class="form-group">
            {!! Form::label('username', 'Username') !!}
            <div class="input-group">
                {!! Form::text('username', '', array('placeholder' => 'Enter your Username', 'class' => 'form-control')) !!}
               
                <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
            </div>
             <strong>{!! $errors->first('username', '<p class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> :message</p>') !!}</strong>
        </div>

        <strong class=''>OR</strong>
<br><br>
        <div class="form-group">
            {!! Form::label('email', 'Email Address') !!}
            <div class="input-group">
                {!! Form::text('email', '', array('placeholder' => 'Enter your Email Address', 'class' => 'form-control')) !!}
                
                <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
            </div>
            <strong>{!! $errors->first('email', '<p class="text-danger"><span class="glyphicon glyphicon-warning-sign"></span> :message </p>') !!}</strong>
        </div>

            {!! Form::submit('Send Temporary Password', array('class' => 'btn btn-primary form-control')) !!}
			
	{!! Form::close() !!}
    </div>
</div>
</div> 
@stop

@section('footerscripts')
<script>
$(document).ready(function() {
    var dis1 = document.getElementById("username");
    dis1.onchange = function () {
       if (this.value != "" || this.value.length > 0) {
          document.getElementById("email").disabled = true;
       }
       else{
        document.getElementById("email").disabled = false;
       }
    }
   var dis2 = document.getElementById("email");
    dis2.onchange = function () {
       if (this.value != "" || this.value.length > 0) {
          document.getElementById("username").disabled = true;
       }
       else{
        document.getElementById("username").disabled = false;
       }
    }
    
});
</script>
@stop