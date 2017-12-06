@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
	<h1>Import to {!! $program->name !!}</h1>
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	
	<div class="reverse-well">
	{!! Form::open(array('files' => true)) !!}
		<div class="form-group">
			{!! Form::label('file', 'Upload CSV to import') !!}
			{!! Form::file('file') !!}
			{!! $errors->first('file', '<p class="text-danger">:message</p>') !!}
		</div>
		
		<div class="form-group">
			<label>Import Type</label>
			<select class="form-control" name="import_type">
				<option value="recipients">Recipient</option>
				<option value="donors">Donor</option>
				<option value="relationships">Sponsorship Relationship</option>
				<option value="payments">Payments</option>
			</select>
		</div>				
		{!! Form::submit('Upload', array('class' => 'btn btn-primary')) !!}
		<a href="{!! URL::to('admin/manage_program') !!}" class="btn btn-default">Cancel</a>
	{!! Form::close() !!}
		<hr>
	    <p><a href="http://help.helpyousponsor.com/read/import" target="blank">Import Instructions</a></p>
	</div>
		
	<div class="modal" id="warn" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h3 class="modal-title text-warning" id="myModalLabel">Important!</h3>
	      </div>
	      <div class="modal-body">
	        <p class="lead">Please review the instructions for importing: <a href="http://help.helpyousponsor.com/read/import" target="blank" class="btn btn-success">Instructions</a></p>
	        	
	        <p>If it has been a while since your last import, please review the import instructions. </p>
		    <p>Successful imports rely on fully understanding the process. Imports cannot be undone.</p>
	        
	        <p><mark>By clicking continue you accept full responsibility for your import.</mark></p> 
	      </div>
	      <div class="modal-footer">
	        <a href="{!! URL::to('admin/manage_program') !!}" class="btn btn-default">Cancel</a>
	        <button type="button" class="btn btn-primary" data-dismiss="modal">Continue</button>
	      </div>
	    </div>
	  </div>
	</div>	

@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		
		$('#warn').modal({
			backdrop: false,
			show: true,
			keyboard: false,
			
		});
		
	});
	</script>
@stop