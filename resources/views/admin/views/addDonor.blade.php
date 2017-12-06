@extends('admin.default')

@section('headerscripts')
	{!! HTML::script('js/jquery.validate.min.js') !!}
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop


@section('content')

	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif
	
	<h1>{!!$hysform->name!!} <small><span class="glyphicon glyphicon-plus"></span> <em>Add Donor</em></small></h1>


@include('admin.views.donorsMenu')

                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
                <div class="panel-actions">
                    <div class="label label-success">New Donor</div>
                </div>
               
                <h3 class="panel-title">Add a Donor to {!! $hysform->name !!}</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">


				<div class="col-md-8">
					{!! Form::open() !!}
						@foreach ($fields as $field)
							<?php $field_type = $field->field_type ?>
							@if ($field_type == 'hysCustomid')
								{!! Form::hidden($field->field_key, true) !!}
							@else
								{!! Form::$field_type($field) !!}
							@endif
						@endforeach
							<div class="form-group">
								{!! Form::label('username', 'Username') !!}
								{!! Form::text('username', $value = null, $attributes = array('placeholder' => 'Enter a username', 'class' => 'form-control', 'autocomplete' => 'off')) !!}
								{!! $errors->first('username', '<p class="text-danger">:message</p>') !!}
							</div>
							
						    <div class="form-group">
						        {!! Form::label('email', 'Email Address') !!}
								{!! Form::email('email', $value = null, $attributes = array('placeholder' => 'Enter valid email address', 'class' => 'form-control', 'autocomplete' => 'off')) !!}
								{!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
						    </div>
							
							<p class="help-text">Username and email must be added in order for the donor to be able to log in to their account and to receive system emails.</p>
						<!-- 	<div class="form-group">
								{!! Form::label('password', 'Password') !!}
								<span class="label label-primary required">Required</span>
								{!! Form::password('password', $attributes = array('class' => 'form-control', 'value required')) !!}
								{!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
							</div> -->
							<div class="form-group">
								<h2>Choose email template to notify donor of their account</h2>
									{!! Form::radio('notify_donor', 'no', true) !!} Don't send notification<br>
								@foreach ($emailsets as $es)
									@if (isset($es['id']))
									{!! Form::radio('notify_donor', $es['id']) !!} Use: {!! $es['name'] !!}<br>
									@endif
								@endforeach
							</div>
							
						{!! Form::submit('Add', array('class' => 'btn btn-primary')) !!}
					{!! Form ::close() !!}
					<br>
					<p><strong>Note:</strong> A temporary password will be created and emailed to the donor if you select a email template.</p>
				</div>
			</div>
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