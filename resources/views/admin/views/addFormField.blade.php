@extends('admin.default')

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<h1><small><a href="{!!URL::to('admin/forms')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Forms  </a></small></h1>

<h2>{!! $hysform->name !!} ({!!$type_name!!}) <small> <span class="glyphicon glyphicon-plus"></span> Add Form Field</small></h2>

@include('admin.views.fieldsMenu')

<div class="app-body">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
                <div class="panel-actions">
                    <div class="label label-success">New Form Field</div>
                </div>
               
                <h3 class="panel-title">Add New Field to: {!! $hysform->name !!}</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">
		{!! Form::open() !!}
		    <div class="form-group">
		        {!! Form::label('field_label', 'Enter Label') !!}
		        <div class="input-group">
		            {!! Form::text('field_label', '', array('placeholder' => 'Minimum of 3 characters', 'class' => 'form-control')) !!}
		            <span class="input-group-addon"><span class="glyphicon glyphicon-tag"></span></span>
		        </div>
				{!! $errors->first('field_label', '<p class="text-danger">:message</p>') !!}
		    </div>
		    
		    <div class="form-group">
		        {!! Form::label('field_type', 'Choose Field Type') !!}
		        <div class="input-group">
					{!!Form::select('field_type',$field_types,null,array('class'=>'form-control'))!!}      
				</div>
		    </div>
		    
		    <div class="form-group">
		        {!! Form::label('field_data', 'Enter Data for the Field') !!}
		        <div class="input-group">
		            {!! Form::text('field_data', '', array('placeholder' => 'Enter additional data', 'class' => 'form-control')) !!}
		            <span class="input-group-addon"><span class="glyphicon glyphicon-question-sign"></span></span>
		        </div>
		        <span class="help-block">Please refer to the instructions for what to enter here. Requirements are different depending on the type of field being added.</span>
		    </div>
		    
		    <div class="form-group">
		        {!! Form::label('permissions', 'Permissions') !!}
		        <div class="input-group">
		            {!! Form::select('permissions', array('public' => 'Everyone', 'donor' => 'Donor and Admins', 'admin' => 'Admins Only'), null, array('class' => 'form-control')) !!}
		        </div>
		    </div>
		    
		    <div class="form-group">
		        {!! Form::label('is_title', 'Title?') !!}
		        <div class="input-group">
		            {!! Form::checkbox('is_title', '1') !!}
		            <span class="help-block">If checked this field will be used as the profile title (or name).</span>
		        </div>
		    </div>
		    
		    <div class="form-group">
		        {!! Form::label('required', 'Required?') !!}
		        <div class="input-group">
		            {!! Form::checkbox('required', '1') !!}
		            <span class="help-block">If checked this field will be required in order to save the profile information.</span>
		        </div>
		    </div>

		    @if($type!='donor')
			    <div class="form-group">
			        {!! Form::label('sortable', 'Sortable?') !!}
			        <div class="input-group">
			            {!! Form::checkbox('sortable', '1') !!}
			            <span class="help-block">If checked the end user will be able to sort with this field.</span>
			        </div>
			    </div>
			    <div class="form-group">
		            {!! Form::label('filter', 'Filter?') !!}
		            <div class="input-group">
		                {!! Form::checkbox('filter', '1') !!}
		                <span class="help-block">If checked the end user will be able to filter with this field. Only works with 'Select List' field type.</span>
		            </div>
		        </div>
		    @endif
		{!! Form::submit('Add', array('class' => 'btn btn-primary')) !!}
		<a href="{!! URL::to('admin/manage_form') !!}/{!! $hysform->id !!}" class="btn btn-default">Cancel</a>
		{!! Form::close() !!}
		</div>
	</div>
	</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {

	});
	</script>
@stop