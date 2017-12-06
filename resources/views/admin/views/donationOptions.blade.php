@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	 <h2>{!!Client::find(Session::get('client_id'))->organization!!} Donations <small> <span class="glyphicon glyphicon-search"></span> Select Donations</small></h2>
	

	@include('admin.views.donationsMenu')

<div class="app-body">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-search"></i></div>
                <div class="panel-actions">
                    <div class="badge"></div>
                </div>
               
                <h3 class="panel-title">Select Donations</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

	{!! Form::open(array('url'=>URL::to('admin/view_donations'),'class' => 'col-md-8 ')) !!}
		<div class="form-group">
			{!! Form::label('Donor Group', 'Select Donor Group') !!}
			<select name="Donor Group" class="form-control" id="Donor_Group">
				<option></option>
				@foreach ($hysforms as $hysform)
				<option value="{!! $hysform->id !!}">{!! $hysform->name !!}</option>
				@endforeach
			</select>
			{!! $errors->first('Donor_Group', '<p class="text-danger">:message</p>') !!}
		</div>
		
		<div class="form-group">
			{!! Form::label('date_from', 'Select start date') !!}
			{!! Form::text('date_from', $value = null, $attributes = array('placeholder' => 'Date format YYYY-MM-DD (Leave Blank For Today)', 'class' => 'form-control datepicker')) !!}
			{!! $errors->first('date_from', '<p class="text-danger">:message</p>') !!}
			
			{!! Form::label('date_to', 'Select end date') !!}
			{!! Form::text('date_to', $value = null, $attributes = array('placeholder' => 'Date format YYYY-MM-DD (Leave Blank For Today)', 'class' => 'form-control datepicker')) !!}
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

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$( ".datepicker" ).datepicker({ dateFormat: "yy-mm-dd" });	
		
		$('#loading').hide();  // hide it initially

		$("#Donor_Group").change(function() {
			var Donor_Group = $(this).val();
			$.ajax({
				url: "{!! URL::to('admin/form_fields') !!}",
				data: {'Donor_Group':Donor_Group},
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
			$('.magic-layout').isotope('reLayout'); // relayout .magic-layout
		});

	});
	</script>
@stop