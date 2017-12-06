@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	<!-- <h1>Edit Email Template Set</h1> -->

	<h1><small><a href="{!!URL::to('admin/email')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Auto Emails </a></small></h1>

	<h2> {!!$emailset->name!!}  <small> <span class="glyphicon glyphicon-pencil"></span> Edit Details</small></h2>

	@include('admin.views.manageEmailTemplatesMenu')
	

<div class="app-body">
	<div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
                <div class="panel-actions">
                </div>
               
                <h3 class="panel-title">Edit Details: {!!$emailset->name!!}</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

				{!! Form::model($emailset) !!}
					<div class="form-group">
						{!! Form::label('name', 'Email Set Name') !!}
						{!! Form::text('name', $value = null, $attributes = array('placeholder' => 'Give this set a name', 'class' => 'form-control')) !!}
						{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
					</div>
					
					<div class="form-group">
						{!! Form::label('from', 'From Email Address') !!}
						{!! Form::text('from', $value = null, $attributes = array('placeholder' => 'Enter email address', 'class' => 'form-control')) !!}
						{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
						<p class="help-block">This is the email address all emails in this set will be sent from. This will also be the email address that receives replies to emails sent from this set.</p>
					</div>
					
					{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
					<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
				{!! Form::close() !!}
			</div>
		</div>
	</div>
</div>
@stop

@section('footerscripts')
@stop