@extends('frontend.default')

@section('content')

<div class="pull-left panel panel-default">

<div class="panel-heading"><h3 class="panel-title">Update Your Account Information</h3></div>

    <div class="panel-body">
        {!! Form::open(array('url'=>'frontend/donor_update_info/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}
       

        <p class="help-block">Fill out the form below to update your personal information.</p>

        @foreach ($donor_fields as $k=> $field)
            <?php 
            $field_type= $field->field_type;

            $field_key= $field->field_key;
            $profile=isset($donor_profile[$field_key])? $donor_profile[$field_key]: '';
            ?>
            <div class="form-group col-md-6 ">
            {!! Form::$field_type($field,$profile) !!}
            {!! $errors->first($field_key, '<p class="text-danger">:message</p>') !!}
            </div>
        @endforeach
        
        

        <div class="col-md-6 form-group">
            {!! Form::label('username', 'Username') !!}
            {!! Form::text('username', $username, $attributes = array('placeholder' => 'Enter a username', 'class' => 'form-control', 'readonly'=>"")) !!}
            {!! $errors->first('username', '<p class="text-danger">:message</p>') !!}

        </div>
        
        <div class="col-md-6 form-group">
            {!! Form::label('email', 'Email Address') !!}
            <span class="label label-primary required pull-right">Required</span>
            <div class="input-group">
            {!! Form::email('email', $email, $attributes = array('placeholder' => 'Enter valid email address', 'class' => 'form-control', 'required'=>"")) !!}
            <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
            </div>
            {!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
        </div>
        
        <div class="col-md-6 form-group">
            {!! Form::label('password', 'Password') !!}
            <div class="input-group">
            
            {!! Form::password('password', $attributes = array('class' => 'form-control')) !!}
            <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
             </div>
            {!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
           
        </div>

         <div class="col-md-6 form-group">
            {!! Form::label('do_not_email', "Don't send me Email. No receipts, no payment reminders, absolutely nothing!") !!}
            <div class="input-group">
            
            {!! Form::checkbox('do_not_email','1',$do_not_email) !!}
             </div>
            {!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
           
        </div>

        <div class="col-md-12 form-group">
            {!! Form::submit('Update My Information', array('class' => 'btn btn-primary form-control')) !!}
            </div>
    {!! Form::close() !!}
    
    </div>

    </div>

@stop