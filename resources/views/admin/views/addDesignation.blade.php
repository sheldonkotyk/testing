@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Designations <small><span class="glyphicon glyphicon-plus"></span> Create Designation </small></h2>
	@include('admin.views.designationsMenu')
	

	<div class="app-body">
                          
            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
                    <div class="panel-actions">
                            <span class="label label-success">New Designation</span>
                    </div>
                    <h3 class="panel-title">Create Designation</h3>
                </div><!-- /panel-heading -->
                <div class="panel-body">
					{!! Form::open() !!}
						<div class="form-group">
							{!! Form::label('name', 'Designation Name') !!}
							{!! Form::text('name', $value = null, $attributes = array('class' => 'form-control')) !!}
							{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
						</div>
						
						<div class="form-group">
							{!! Form::label('code', 'Accounting Code') !!}
							{!! Form::text('code', $value = null, $attributes = array('class' => 'form-control')) !!}
							<p class="help-text">The accounting code is not required, it is only for your accounting purposes.</p>
						</div>
						
						<hr>
						{!! Form::label('hysform', 'Choose which donor form this designation will be used on') !!}
						{!! Form::select('hysform',$hysforms,null,$attributes = array('class' => 'form-control')) !!}

						<br>
						<div class="form-group">
							{!! Form::label('emailset_id', 'Choose email set to use with this designation') !!}
							<select class="form-control" name="emailset_id">
								@foreach ($emailsets as $emailset) 
									<option value="{!! $emailset->id !!}">{!! $emailset->name !!}</option>
								@endforeach
							</select>
						</div>
								
						<hr>
						{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
						<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
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