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
				@foreach ($profile_fields as $pf)
					@if (isset($entity[$pf->field_key]))
					<div class="form-group">
						<label>{!! $pf->field_label !!}</label>
						<input class="form-control" type="text" value="{!! $entity[$pf->field_key] !!}" disabled>
						<input type="hidden" name="{!! $pf->field_key !!}" value="{!! $entity[$pf->field_key] !!}">
					</div>
					@endif
				@endforeach
				
				@foreach ($fields as $field)
					<?php $field_type = $field->field_type ?>
					{!! Form::$field_type($field) !!}
				@endforeach
				
				{!! Form::submit('Submit', array('class' => 'btn btn-primary')) !!}
				<a href="{!! URL::to('admin/list_archived_forms', array('entity',$id,$hysform->id)) !!}" class="btn btn-default">Cancel</a>
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