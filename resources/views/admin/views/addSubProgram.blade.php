@extends('admin.default')

@section('content')

<h2>{!!Client::find(Session::get('client_id'))->organization!!} Programs <small> <span class="glyphicon glyphicon-plus"></span> Create Subordinate Program</small></h2>

@include('admin.views.programsMenu')

<div class="app-body">
<div id="panel-bsbutton" class="panel panel-default magic-element">
    <div class="panel-heading">
        <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
        <div class="panel-actions">
            <div class="label label-success">New Sub Program</div>
        </div>
       
        <h3 class="panel-title">Create Subordinate Program</h3>
    </div><!-- /panel-heading -->
    <div class="panel-body">

    {!! Form::open() !!}
        <div class="form-group">
            {!! Form::label('name', 'Enter Name') !!}
            {!! Form::text('name', '', array('placeholder' => 'Minimum of 3 characters', 'class' => 'form-control','required')) !!}
			{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
        </div>

        <div class="form-group">
            {!! Form::label('link_id', 'Select Parent Program') !!}
            {!! Form::select('link_id', $programs,'', array('class' => 'form-control')) !!}
            <p class="help-text">This is the program from which this sub-program will draw it's recipients.</p>
        </div>
         
        	{!! Form::hidden('parent_id', $parent_id)!!}
       {!! Form::submit('Create', array('class' => 'btn btn-primary')) !!}
       <a href="{!! URL::to('/admin/manage_program') !!}" class="btn btn-default">Cancel</a>
    {!! Form::close() !!}
    </div>
    </div>
    </div>
@stop