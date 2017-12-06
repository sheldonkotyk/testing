@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<h2>{!!Client::find(Session::get('client_id'))->organization!!} Auto Emails <small> <span class="glyphicon glyphicon-plus"></span> Create Auto Email Set</small></h2>
	
	@include('admin.views.manageEmailTemplatesMenu')

	<div class="app-body">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
                <div class="panel-actions">
                    <div class="label label-success">New Auto Email Set</div>
                </div>
               
                <h3 class="panel-title">Create Auto Email Set</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

			{!! Form::open() !!}
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
@stop

@section('footerscripts')
@stop