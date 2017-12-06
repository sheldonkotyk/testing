@extends('admin.default')

@section('headerscripts')
@stop

@section('content')

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Forms <small> <span class="glyphicon glyphicon-plus"></span> Create Form</small></h2>
	@include('admin.views.formsMenu')

<div class="app-body">
	<div class="magic-layout">
                            
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
		    <div class="panel-heading">
		        <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
		        <div class="panel-actions">
		            <div class="label label-success">New Form</div>
		        </div>
		       
		        <h3 class="panel-title">Create Form</h3>
		    </div><!-- /panel-heading -->
		    <div class="panel-body">

				{!! Form::open() !!}
					<div class="form-group">
						{!! Form::label('name', 'Form Name') !!}
						{!! Form::text('name', $value = null, $attributes = array('placeholder' => 'Give the form a name', 'class' => 'form-control', 'required')) !!}
						{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
					</div>
					
					<div class="form-group">
						{!! Form::label('type', 'Form Type') !!}
						{!! Form::select('type', array('entity' => 'Recipient Profile', 'donor' => 'Donor Profile', 'submit' => 'Progress Report'), $default_type, array('class' => 'form-control')) !!}
					</div>	
						
					{!! Form::submit('Create', array('class' => 'btn btn-primary')) !!}
					<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
				{!! Form::close() !!}
				
			</div>
		</div>
	</div>
	</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$('div#send_to').hide();
		
		$('select#type').change(function() {
			var selected = $(this).val();
			if (selected == 'submit') {
				$('div#send_to').show();
			} else {
				$('div#send_to').hide();
			}
		});
	});
	</script>
@stop