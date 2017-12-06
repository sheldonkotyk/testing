@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	
	<h2>Select Admin Groups to Notify</h2>
	<div class="reverse-well">
		<p class="lead">When the form is submitted for this program the checked admin groups will be notified.</p>
		{!! Form::open() !!}
			
			@foreach ($groups as $group)
				<?php if (in_array($group->id, $current)) { $checked = 'checked="checked"'; } else { $checked = ''; } ?>
				
				<div class="checkbox">
					<label>
						<input type="checkbox" name="group[]" value="{!! $group->id !!}" {!! $checked !!}> {!! $group->name !!}
					</label>
				</div>
			@endforeach
	
			{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
			<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
		{!! Form::close() !!}
	</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
	});
	</script>
@stop