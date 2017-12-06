@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif


    <h1><small><a href="{!!URL::to('admin/view_admins')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Admins </a></small></h1>

	<h2>{!!$admin->first_name!!} {!!$admin->last_name!!}<small> <span class="glyphicon glyphicon-pencil"></span> Edit Admin</small></h2>

	@include('admin.views.adminsMenu')

	<!-- <p><a href="{!! URL::to('admin/add_admin') !!}" class="btn btn-default">Add Admin <span class="glyphicon glyphicon-plus"></span></a></p> -->

<div class="app-body">
	<div class="magic-layout">
			<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
	                <div class="panel-actions">
	                    <div class="badge"></div>
	                </div>
	               
	                <h3 class="panel-title">Edit {!!$admin->first_name!!} {!!$admin->last_name!!}</h3>
	            </div><!-- /panel-heading -->
	            <div class="panel-body">
						{!! Form::model($admin) !!}
					        <div class="form-group">
					            {!! Form::label('first_name', 'First Name') !!}
					            <div class="input-group">
						            {!! Form::text('first_name', $value = null, array('class' => 'form-control')) !!}
						            <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
					            </div>
					            {!! $errors->first('first_name', '<p class="text-danger">:message</p>') !!}
					        </div>
					        
					        <div class="form-group">
					            {!! Form::label('last_name', 'Last Name') !!}
					            <div class="input-group">
						            {!! Form::text('last_name', $value = null, array('class' => 'form-control')) !!}
						            <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
					            </div>
					        </div>
					        <div class="form-group">
					            {!! Form::label('email', 'Email') !!}
					            <div class="input-group">
						            {!! Form::text('email', $value = null, array('placeholder' => 'Enter your email address', 'class' => 'form-control')) !!}
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
					        	{!! Form::label('group_id', 'Admin Group') !!}
					        	<div class="input-group">
					        		{!! Form::select('group_id', $groups, null, array('class' => 'form-control')) !!}
					        	</div>
					        </div>

					        @if($admin->id!=$user->id)
					        <div class="btn-group pull-right"><a href="{!! URL::to('admin/remove_admin', array($admin->id)) !!}">
			                <button type="button" class="btn btn-danger">
			                   <span class="glyphicon glyphicon-remove"></span> Delete Admin
			                </button></a></div>
			                @else

			                 <div class="btn-group pull-right">
							<span>You May not delete yourself.</span><br/>
			                 <a href="#">
			                <button type="button" class="btn btn-danger" disabled="disabled">
			                   <span class="glyphicon glyphicon-remove"></span> Delete Admin
			                </button></a>
			                </div>
			                @endif
							
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