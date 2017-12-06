@extends('admin.default')

@section('headerscripts')
	{!! HTML::script('js/jquery.validate.min.js') !!}
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
	<h2>{!! $hysform->name !!}</h2>
	<div class="row">
		<div class="col-md-8 reverse-well">
			{!! Form::open() !!}
				@foreach ($fields as $field)
					<?php $field_type = $field->field_type ?>
					<?php $value = ''; ?>
					@foreach ($form_info as $f)
						@if ($f['field_key'] == $field->field_key)
							<?php $value = $f['data']; ?>
						@endif
					@endforeach
					
					{!! Form::$field_type($field, $value) !!}
				@endforeach
				
				{!! Form::submit('Submit', array('class' => 'btn btn-primary')) !!}
				<a href="{!! URL::to('admin/view_archived_form', array($form->id)) !!}" class="btn btn-default">Cancel</a>
			{!! Form ::close() !!}
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