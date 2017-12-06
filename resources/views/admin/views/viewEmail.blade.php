@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
    
    
    <h1><small><a href="{!!URL::to('admin/email_manager')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Message Manager </a></small></h1>
	
	<h2> {!!ucfirst($email['from'])!!} Message <small> <span class="icon ion-ios7-browsers"></span> View Message from: {!! $email['from_name']['name'] !!}</small></h2>
	
	@include('admin.views.donorEmailMenu')

	@if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<div class="app-body">

                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-envelope"></i></div>
                <div class="panel-actions">
                    <div class="badge"></div>
                </div>
               
                <h3 class="panel-title">View Message</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

	<div class="row">
		<div class="col-md-8">

		@if($email['from']=='entity')
			<div class="panel panel-info">
		@else
			<div class="panel panel-success">
		@endif
				<div class="panel-heading"><strong>From</strong>: {!! $email['from_name']['name'] !!} <br><strong>To</strong>: {!! $email['to']['name'] !!} <br> @if (!empty($email['subject']))Subject: {!! $email['subject'] !!} @else No Subject @endif <span class="pull-right">{!! Carbon::createFromTimeStamp(strtotime($email['date']))->toFormattedDateString() !!}</span></div>
				<div class="panel-body">
				<h4></h4>
					<p>{!! $email['message'] !!}</p>
				</div>
				<div class="panel-footer">
					Status:
					<span id="status">
					@if ($email['status'] == 1 || empty($email['status']))
					<span class="label label-success">New</span>
					@elseif ($email['status'] == 2)
					<span class="label label-warning">In Process</span>
					@elseif ($email['status'] == 3)
					<span class="label label-default">Complete</span>
					@endif
					</span> <small><em class="text-muted">* Click to change</em></small>
					
					@foreach ($admins as $admin)
						@if ($email['admin_assigned'] == $admin->id)
							<p class="pull-right"><span class="label label-primary">Admin Assigned: {!! $admin->first_name !!} {!! $admin->last_name !!}</span></p>
						@endif
					@endforeach
					
				</div>
			</div>
			
			@foreach ($responses as $response)
			@if($email['from']=='entity')
				<div class="panel panel-success">
			@else
				<div class="panel panel-info">
			@endif

				<div class="panel-heading"> <strong>From</strong>: {!! $email['to']['name'] !!}<br> <strong>To</strong>: {!! $email['from_name']['name'] !!} <br> RE: {!! $email['subject'] !!}  <span class="pull-right">{!! Carbon::createFromTimeStamp(strtotime($response->created_at))->toFormattedDateString() !!}</span></div>
				<div class="panel-body">
					<p>{!! $response->message !!}</p>
				</div>
			</div>
			@endforeach
			
			{!! Form::open(array('url' => 'admin/send_email_response/'.$email['id'].'', 'class' => 'reverse-well')) !!}
			<div class="form-group">
				{!! Form::label('response', 'Send Response to: '.$email['from_name']['name']) !!}
				{!! Form::textarea('response', $value = null, $attributes = array('class' => 'form-control hysTextarea')) !!}
			</div>
			@if(empty($disabled))
				{!! Form::submit('Send', array('class' => 'btn btn-primary')) !!}
			@else
				{!! Form::submit('Send', array('class' => 'btn btn-primary','disabled'=>'disabled')) !!}
			@endif
			{!! Form::close() !!}
		</div>
		<div class="col-md-4">			
			{!! Form::open(array('url' => 'admin/assign_admin/'.$email['id'].'', 'class' => 'reverse-well')) !!}
				<legend>Assign an admin</legend>
				<div class="form-group">
					<select class="form-control" name="admin">
						<option></option>
						@foreach ($admins as $admin)
							@if ($email['admin_assigned'] == $admin->id)
								<option value="{!! $admin->id !!}" selected="selected">{!! $admin->first_name !!} {!! $admin->last_name !!}</option>
							@else
								<option value="{!! $admin->id !!}">{!! $admin->first_name !!} {!! $admin->last_name !!}</option>
							@endif
						@endforeach
					</select>
				</div>
			{!! Form::submit('Set', array('class' => 'btn btn-primary')) !!}			
			{!! Form::close() !!}
						
		</div>
	</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$('.hysTextarea').redactor();
		
		$('#status').css( 'cursor', 'pointer' );
		
		$(document).on('click', '#status', function() {
			$.ajax({
				url: "{!! URL::to('admin/update_email_status', array($email['id'])) !!}",
				data: {},
				cache: 'false',
				dataType: 'html',
				type: 'get',
				success: function(html, textStatus) {
					$('#status').html(html);
				}
			});
		});
	});
	</script>
@stop