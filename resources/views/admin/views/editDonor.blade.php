@extends('admin.default')

@section('headerscripts')
	{!! HTML::script('js/jquery.validate.min.js') !!}
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
		{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	
@stop

@section('content')
	
	<h1><small><a href="{!!URL::to('admin/show_all_donors',array($hysform->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $hysform->name !!} </a></small>
	<div class="btn-group"><a href="{!! URL::to('admin/add_donor', array($hysform->id)) !!}">

            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-plus"></span> Add Donor
            </button></a></div>

	</h1>
	<h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $name !!} <small><span class="glyphicon glyphicon-pencil"></span> <em>Edit Profile</em>@if($donor['deleted_at']!=null) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small></small>  </h1>
	
	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif

	@include('admin.views.donorMenu')
<div class="app-body">
	    <!-- app content here -->
	    <div class="magic-layout">
	                     
			<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
	               	<div class="panel-actions">
	               		@foreach($details as $k => $d)
	                    	<div class="label label-success">{!!$k!!} {!!$d!!}</div>
	                    @endforeach
	                </div>
	                <h3 class="panel-title">Edit {!!$name!!}'s Profile</h3>
	            </div><!-- /panel-heading -->
	            <div class="panel-body">

					{!! Form::open() !!}
						@foreach ($fields as $field)
							<?php $field_type = $field->field_type ?>
							@if (isset($profile[$field->field_key]))
								{!! Form::$field_type($field, $profile[$field->field_key]) !!}
							@else
								{!! Form::$field_type($field) !!}
							@endif
						@endforeach
						

						<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
						<input style="display:none" type="text" name="fakeusernameremembered"/>
						<input style="display:none" type="password" name="fakepasswordremembered"/>

						<div class="form-group">
							{!! Form::label('username', 'Username') !!}
							{!! Form::text('username', $donor->username, $attributes = array('placeholder' => 'Enter a username', 'class' => 'form-control', 'autocomplete' => 'off')) !!}
							{!! $errors->first('username', '<p class="text-danger">:message</p>') !!}
						</div>
						
					    <div class="form-group">
					        {!! Form::label('email', 'Email Address') !!}
							{!! Form::email('email', $donor->email, $attributes = array('placeholder' => 'Enter valid email address', 'class' => 'form-control', 'autocomplete' => 'off')) !!}
							{!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
					    </div>
						
						<div class="form-group">
							{!! Form::label('password', 'Password') !!}
							{!! Form::password('password', $attributes = array('class' => 'form-control', 'autocomplete' => 'off')) !!}
							{!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
						</div>
						
						{!! Form::submit('Update', array('class' => 'btn btn-primary')) !!}
						<a href="{!! URL::to('admin/show_all_donors', array($donor->hysform_id)) !!}" class="btn btn-default">Cancel</a>
					{!! Form ::close() !!}
						
					</div>
					@if(isset($profilePic))
						<div class="col-md-4">
							<img src="{!! $profilePic !!}" class="img-rounded img-responsive" />
						</div>
					@endif
					</div>
					</div>
					</div>

@stop

@section('footerscripts')
<script>
$(document).ready(function() {
		$('.hysTextarea').redactor();
		$("form").validate();
		$( ".datepicker" ).datepicker({ 
		dateFormat: "yy-mm-dd", 
		changeMonth: true,
		changeYear: true
		});
	
});
</script>
@stop