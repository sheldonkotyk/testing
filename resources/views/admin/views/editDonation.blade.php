@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
     <h2>Edit Donation</h2>
     <div class="reverse-well">
        <p class="lead">IMPORTANT! Editing a donation does not effect charges to a credit card. Deleting a donation will not refund a donation to the donor.</p>
        	{!! Form::model($donation) !!}
        		<div class="form-group">
	        		{!! Form::label('created_at', 'Date') !!}
	        		{!! Form::text('created_at', $value = null, array('class' => 'form-control edit-date')) !!}
        		</div>
        		
        		<div class="form-group">
	        		{!! Form::label('amount', 'Amount') !!}
	        		{!! Form::text('amount', sprintf("%01.2f", $donation->amount), array('class' => 'form-control')) !!}
        		</div>
        		
        		<div class="form-group">
	        		{!! Form::label('result', 'Result') !!}
	        		{!! Form::textarea('result', $value = null, array('class' => 'form-control hysTextarea')) !!}
        		</div>
        
        <div class="form-group">	
	        <a class="btn btn-default" href="{!! URL::to('admin/donations_by_donor', array($donation->donor_id)) !!}">Cancel</a>
	        {!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
	        <a class="btn btn-danger" href="{!! URL::to('admin/remove_donation', array($donation->id)) !!}">Delete Donation</a>
        </div>
        {!! Form::close() !!}
	</div>
@stop

@section('footerscripts')
<script>
	$(document).ready(function() {			
		$('.hysTextarea').redactor();
		$("input.edit-date").datepicker({ dateFormat: "yy-mm-dd" });
	});
</script>
@stop
