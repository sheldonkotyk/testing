@extends('frontend.default')

@section('headerscripts')
    {!! HTML::script('js/jquery.validate.min.js') !!}
    {!! HTML::style('css/redactor.css') !!}
    {!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
    <div class="col-md-6 col-md-offset-3 well">
   
    <h1>Sign up</h1>
    <hr>
    {!! Form::open(array('url' => 'frontend/signup_donor/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}
        @foreach ($fields as $field)
            <?php $field_type= $field->field_type ?>
            {!! Form::$field_type($field) !!}
        @endforeach
            <div class="form-group">
                {!! Form::label('username', 'Username') !!}
                 <span class="label label-primary required">Required</span>
                {!! Form::text('username', $value = null, $attributes = array('placeholder' => 'Enter a username', 'class' => 'form-control', 'value required')) !!}
                {!! $errors->first('username', '<p class="text-danger">:message</p>') !!}

            </div>
            
            <div class="form-group">
                {!! Form::label('email', 'Email Address') !!}
                <span class="label label-primary required">Required</span>
                {!! Form::email('email', $value = null, $attributes = array('placeholder' => 'Enter valid email address', 'class' => 'form-control', 'value required')) !!}
                {!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
            </div>
            
            <div class="form-group">
                {!! Form::label('password', 'Password') !!}
                <span class="label label-primary required">Required</span>
                {!! Form::password('password', $attributes = array('class' => 'form-control', 'value required')) !!}
                {!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
            </div>
            
        {!! Form::submit('Sign Up!', array('class' => 'btn btn-primary')) !!}
    {!! Form ::close() !!}
</div>
@stop
@section('footerscripts')
<script>
$(document).ready(function() {
        $('.hysTextarea').redactor();
        $("form").validate();
    
});
</script>
@stop