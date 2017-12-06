@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	

	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Admins<small> <span class="glyphicon glyphicon-plus"></span> Create Admin</small></h2>

	@include('admin.views.adminsMenu')
<div class="app-body">
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
                <div class="panel-actions">
                    <div class="label label-success">New Admin</div>
                </div>
               
                <h3 class="panel-title">Create Admin</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

			{!! Form::open() !!}
		        <div class="form-group">
		            {!! Form::label('first_name', 'First Name') !!}
		            <div class="input-group">
			            {!! Form::text('first_name', '', array('class' => 'form-control')) !!}
			            <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
		            </div>
		            {!! $errors->first('first_name', '<p class="text-danger">:message</p>') !!}
		        </div>
		        
		        <div class="form-group">
		            {!! Form::label('last_name', 'Last Name') !!}
		            <div class="input-group">
			            {!! Form::text('last_name', '', array('class' => 'form-control')) !!}
			            <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
		            </div>
		        </div>
		        <div class="form-group">
		            {!! Form::label('email', 'Email') !!}
		            <div class="input-group">
			            {!! Form::text('email', '', array('placeholder' => 'Enter your email address', 'class' => 'form-control')) !!}
			            <span class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></span>
		            </div>
		            {!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
		        </div>
		
		        <div class="form-group">
		            {!! Form::label('password', 'Password') !!}
		            <div class="input-group">
		            	{!! Form::password('password', $attributes = array('placeholder' => 'Enter your password', 'class' => 'form-control')) !!}
			            <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
		            </div>
		            {!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
		        </div>
		        
		        <div class="form-group">
		        	{!! Form::label('group', 'Admin Group') !!}
		        	<div class="input-group">
		        		{!! Form::select('group', $groups, null, $attributes = array('class' => 'form-control')) !!}
		        	</div>
		        </div>
				
				{!! Form::submit('Create', array('class' => 'btn btn-primary')) !!}
				<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
			{!! Form::close() !!}
		</div>
	</div>
	</div>
@stop

@section('footerscripts')
@stop