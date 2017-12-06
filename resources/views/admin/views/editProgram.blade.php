@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
   

<h1><small><a href="{!!URL::to('admin/manage_program')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Programs  </a></small></h1>

    <h2>{!! $program->name !!} <small><span class="icon ion-wrench"></span> Edit Program Details </small></h2>	

   @include('admin.views.programsMenu')

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif


    <div class="app-body">
    <div class="magic-layout">
                          
            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
                    <div class="panel-actions">
                            <div class="label label-success"></div>
                    </div>
                    <h3 class="panel-title">Edit Program Details</h3>
                </div><!-- /panel-heading -->
                <div class="panel-body">

                	{!! Form::model($program) !!}
                		<div class="form-group">
                			{!! Form::label('name', 'Program Name') !!}
                			{!! Form::text('name', $value = null, $attributes = array('placeholder' => 'Give the form a name', 'class' => 'form-control','required')) !!}
                			{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
                		</div>
                		
                        @if($program->link_id== 0)
                            <div class="form-group">
                            	{!! Form::label('prefix', 'Enter Program Abbreviation') !!}
                            	{!! Form::text('prefix', $value = null, array('class' => 'form-control')) !!}
                            	<p class="help-text">This will be appended to the beginning of the software generated ID's created for this program.</p>
                            </div>
                        @endif

                        @if($program->link_id!= 0)

                        	<div class="form-group">
                            {!! Form::label('link_id', 'Select Parent Program') !!}
                            {!! Form::select('link_id', $programs,$program->link_id, array('class' => 'form-control')) !!}
                            <p class="help-text">This is the program from which this sub-program will draw it's entities.</p>
                        </div>

                        @endif
                        
                        @if($program->link_id== 0)
                            <div class="form-group">
                            	{!! Form::label('counter', 'Counter') !!}
                            	{!! Form::text('counter', $value = null, array('class' => 'form-control')) !!}
                            	{!! $errors->first('counter', '<p class="text-danger">:message</p>') !!}
                            	<p class="help-text">This is the current number of the counter. You may change it to a new number from which the counter increments.</p>
                            </div>
                        @endif
                		
                		{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
                		<a href="{!! URL::to('admin/manage_program') !!}" class="btn btn-default">Cancel</a>
                	{!! Form::close() !!}
                	</div>
                </div>
            </div>
        </div>

@stop

@section('footerscripts')
@stop