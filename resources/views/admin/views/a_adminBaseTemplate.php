@extends('admin.default')

@section('headerscripts')
	{{ HTML::style('css/jquery-ui.min.css') }}
	{{ HTML::script('js/jquery-ui-1.10.3.custom.min.js') }}	

	{{ HTML::style('css/redactor.css') }}
	{{ HTML::script('js/redactor.min.js') }}
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{{ Session::get('alert') }}">{{ Session::get('message') }}</div>
    @endif

	{{ Form::open() }}
		<div class="form-group">
			{{ Form::label('name', 'Form Name') }}
			{{ Form::text('name', $value = null, $attributes = array('placeholder' => 'Give the form a name', 'class' => 'form-control')) }}
			{{ $errors->first('name', '<p class="text-danger">:message</p>') }}
		</div>
		
		<div class="form-group">
			{{ Form::label('type', 'Form Type') }}
			{{ Form::select('type', array('entity' => 'Sponsorship Profile', 'donor' => 'Donor Profile'), null, array('class' => 'form-control')) }}
		</div>
		
		{{ Form::submit('Save', array('class' => 'btn btn-primary')) }}
		<a href="{{ URL::previous() }}" class="btn btn-default">Cancel</a>
	{{ Form::close() }}
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