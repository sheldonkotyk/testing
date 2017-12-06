@extends('admin.default')

@section('headerscripts')
	{!! HTML::script('js/jquery.validate.min.js') !!}
	<script>
		$("form").validate();
	</script>

	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

@stop

@section('content')

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

@if (isset($programData['error']))
    <div class="alert alert-danger">{!! $programData['error'] !!}</div>
@else

<h1>{!!$program->name!!} <small><span class="glyphicon glyphicon-plus"></span> <em>Add Recipient</em></small></h1>

@include('admin.views.programMenu')

                                        
		<div id="panel-bsbutton" class="panel panel-default">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
                <div class="panel-actions">
                    <div class="label label-success">New Recipient</div>
                </div>
               
                <h3 class="panel-title">Add a Recipient to {!! $program->name !!}</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">
	
			<div class="col-md-8">
	
				{!! Form::open() !!}
				@foreach ($fields as $field)
					<?php $field_type = $field->field_type ?>
					@if ($field_type == 'hysCustomid')
						{!! Form::hidden($field->field_key, true) !!}
						<p><em>ID number will automatically be added once profile is saved</em></p><br>
					@else
						{!! Form::$field_type($field) !!}
					@endif
				@endforeach
				
				@if($programData['type'] == 'contribution')
					<div class="form-group">
						{!! Form::label('sp_num', 'Contribution Level Required') !!}
						@if(empty($programData['sp_num']['amount']))
							{!!Form::text('sp_num','',array('class'=>'form-control'))!!}
						@else
						<select class="form-control" name="sp_num">
							@foreach ($programData['sp_num'] as $num) 
								<option value="{!! $num['amount'] !!}">{!! $num['symbol'] !!}{{ $num['amount'] }}</option>
							@endforeach
						</select>
						@endif
						{!! $errors->first('sp_num', '<p class="text-danger">:message</p>') !!}
					</div>
				@endif
				
				@if($programData['type'] == 'number')
					<div class="form-group">
						{!! Form::label('sp_num', 'Number of Sponsors Required') !!}
						@if (count($programData['number_sponsors']) > 1)
						<select class="form-control" name="sp_num">
							@foreach ($programData['number_sponsors'] as $num) 
								<option value="{!! $num !!}">{!! $num !!}</option>
							@endforeach
						</select>
						@else
							@foreach ($programData['number_sponsors'] as $num) 
								<br>{!! $num !!}
								<input type="hidden" name="sp_num" value="{!! $num !!}">
							@endforeach
						@endif
					</div>
			
					<div class="form-group">
						{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
						@if (count($programData['sp_amount']) > 1)
						<select class="form-control" name="sp_amount">
							@foreach ($programData['sp_amount'] as $sp_amount) 
								<option value="{!! $sp_amount['amount'] !!}">{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}</option>
							@endforeach
						</select>
						@else					
						@foreach ($programData['sp_amount'] as $sp_amount)
							<br>{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}
							<input type="hidden" name="sp_amount" value="{!! $sp_amount['amount'] !!}">
						@endforeach
						@endif
					</div>
				@endif
				{!! Form::submit('Add Recipient', array('class' => 'btn btn-primary form-control')) !!}
				{!! Form ::close() !!}
			</div>
		</div>
@endif
</div> <!-- end #panel-bsbutton -->

@stop

@section('footerscripts')
<script type="text/javascript">
$(document).ready(function(){
	$( ".datepicker" ).datepicker({ 
		dateFormat: "yy-mm-dd", 
		changeMonth: true,
		changeYear: true
	});
	$('.hysTextarea').redactor();
});
</script>
@stop