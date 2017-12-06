@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/typeahead.js-bootstrap.css') !!}
	<script>
	$(document).ready(function() {
		$('input#categories').typeahead({
		  name: 'categories',
		  prefetch: {
		  	url: '{!! URL::to('admin/list_cat') !!}/{!! $type !!}/{!! $program_id !!}',
		  	ttl: 1
		  }
		});
	});
	</script>
@stop

@section('content')

	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif

     <div class="reverse-well">   
	{!! Form::model($note) !!}
		<legend>Edit Note</legend>
		<div class="form-group">
			{!! Form::textarea('note', $value = null, array('class' => 'form-control', 'rows' => '2')) !!}
		</div>
	    
		<div class="form-group">
			{!! Form::label('categories', 'Category') !!}
			<div class="input-group">
				{!! Form::text('categories', '', array('class' => 'form-control')) !!}
			</div>
			<p class="help-text">Leave blank to leave the category unchanged.</p>
		</div>
		{!! Form::hidden('entity_id', $note->entity_id) !!}
		@if ($type == 'entity')
			<a href="{!! URL::to('admin/notes') !!}/{!! $note->entity_id !!}/{!! $type !!}/{!! $program_id !!}" class="btn btn-default">Cancel</a>
		@elseif ($type == 'donor')
			<a href="{!! URL::to('admin/notes') !!}/{!! $note->donor_id !!}/{!! $type !!}/{!! $program_id !!}" class="btn btn-default">Cancel</a>
		@endif
	{!! Form::submit('Save Changes', array('class' => 'btn btn-primary')) !!}
	{!! Form::close() !!}
	</div>
@stop

@section('footerscripts')
	{!! HTML::script('js/typeahead.min.js') !!}
@stop