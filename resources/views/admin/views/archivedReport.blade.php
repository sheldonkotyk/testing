@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	 <h2>{!!Client::find(Session::get('client_id'))->organization!!} Progress Reports <small> <span class="icon ion-ios7-browsers"></span> View Progress Reports</small></h2>
	
	@include('admin.views.progressReportsMenu')
<div class="app-body">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-ios7-browsers"></i></div>
                <div class="panel-actions">
                    <div class="badge"></div>
                </div>
               
                <h3 class="panel-title">View Progress Reports</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

				{!! Form::open() !!}
					<div class="form-group">
						{!! Form::label('type', 'Select for Donors or Sponsorships') !!}
						<select name="type" class="form-control">
							<option></option>
							<option value="donor">Donors</option>
							<option value="entity">Sponsorships</option>
						</select>
					</div>
				
					<div class="form-group">
						{!! Form::label('hysform', 'Select Form') !!}
						<select name="hysform_id" class="form-control">
							<option></option>
							@foreach ($hysforms as $hysform)
							<option value="{!! $hysform->id !!}">{!! $hysform->name !!}</option>
							@endforeach
						</select>
					</div>
					
					<div class="form-group">
						{!! Form::label('program', 'Select Program') !!}
						<select name="program_id" class="form-control">
							<option></option>
							@foreach ($programs as $program)
							<option value="{!! $program->id !!}">{!! $program->name !!}</option>
							@endforeach
						</select>
					</div>
					
					<div class="form-group">
						{!! Form::label('date_from', 'Select start date') !!}
						{!! Form::text('date_from', $value = null, $attributes = array('placeholder' => 'Date format YYYY-MM-DD', 'class' => 'form-control datepicker')) !!}
						{!! $errors->first('date_from', '<p class="text-danger">:message</p>') !!}
						
						{!! Form::label('date_to', 'Select end date') !!}
						{!! Form::text('date_to', $value = null, $attributes = array('placeholder' => 'Date format YYYY-MM-DD', 'class' => 'form-control datepicker')) !!}
						{!! $errors->first('date_to', '<p class="text-danger">:message</p>') !!}
					</div>
					<div id="loading" class="well col-md-offset-4 col-md-4">
					<p>Loading...</p>
						<div class="progress progress-striped active">
						  <div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
						  </div>
						</div>		
					</div>
					<div class= "clearfix" id="form_fields">
					</div>
					<hr>
					{!! Form::submit('Submit', array('class' => 'btn btn-primary')) !!}
					
				{!! Form::close() !!}

				</div>
			</div>
	</div>
@stop

{{--this is disabled for now we just dump out all fields--}}
@section('footerscripts')
	<script>
	$(document).ready(function() {
		$( ".datepicker" ).datepicker({ dateFormat: "yy-mm-dd" });	
		
		$('#loading').hide();  // hide it initially

		$("#hysform_id").change(function() {
			var hysform_id = $(this).val();
			$.ajax({
				url: "{!! URL::to('admin/form_fields') !!}",
				data: {'hysform_id':hysform_id},
				cache: 'false',
				dataType: 'html',
				type: 'get',
				beforeSend: function() {
					$("#form_fields").html('');
			    	$('#loading').show();
				},
				complete: function(){
					$('#loading').hide();
				},
				success: function(html, textStatus) {
					$('div#form_fields').html(html);
				}
			});
		});
	});
	</script>
@stop