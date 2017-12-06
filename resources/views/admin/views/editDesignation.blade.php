@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
    <h1><small><a href="{!!URL::to('admin/all_designations')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Designations </a></small>

    <div class="pull-right">
            <small>Share:</small>
                <a class="btn btn-xs btn-default btn-extend be-left" href="https://twitter.com/share?url={!!URL::to('frontend/orderD',array(Session::get('client_id'),$designation->hysforms,$designation->id))!!}&text=Contribute to {!!$designation->name!!}:" target="_blank">
                        <i class="icon ion-social-twitter"></i>Tweet</a> 
                 <a class="btn btn-xs btn-default btn-extend be-left" href="https://www.facebook.com/sharer/sharer.php?u={!!URL::to('frontend/orderD',array(Session::get('client_id'),$designation->hysforms,$designation->id))!!}&display=popup" target="_blank">
                        <i class="icon ion-social-facebook"></i>Share</a> 
                 <a class="btn btn-xs btn-default btn-extend be-left" href="mailto:?subject=Contribute%20to%20{!!$designation->name!!}&body=Click%20on%20the%20link%20to%20contribute%20to%20{!!$designation->name!!}%0D%0A{!!URL::to('frontend/orderD',array(Session::get('client_id'),$designation->hysforms,$designation->id))!!}">
                         Email
                          <i class="glyphicon glyphicon-envelope"></i>
                         </a>
                    <a class="btn btn-xs btn-default btn-extend be-left" data-toggle="collapse" href="#collapseTwo">
                        <i class="glyphicon glyphicon-link"></i> Embed</a>
            </div>
            </h1>
	<h2>{!!$designation->name!!} <small><span class="glyphicon glyphicon-pencil"></span> Edit Additional Gift</small></h2>
	@include('admin.views.designationsMenu')
	

<div class="app-body">

       <div id="collapseTwo" class="panel panel-default panel-collapse collapse">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
                    <div class="panel-actions">
                            <div class="label label-success">Info</div>
                    </div>
                    <h3 class="panel-title"> Share <strong> {!!$designation->name!!}</strong></h3>
                </div><!-- /panel-heading -->
                      <div class="panel-body">
                      <h4 >Iframe Embed Code </h4>
                      <pre class="prettyprint">&lt;iframe class="hysiframe" src="{!!URL::to('frontend/orderD',array(Session::get('client_id'),$designation->hysforms,$designation->id))!!}" style="border:0px #FFFFFF none;" name="HYSiFrame" scrolling="no" frameborder="1" height="1500px" marginheight="0px" marginwidth="0px" width="100%"&gt;&lt;/iframe&gt;</pre>
                      <br>
                      <h4><strong>{!!$designation->name!!}</strong> Frontend Link: <a href="{!!URL::to('frontend/orderD',array(Session::get('client_id'),$designation->hysforms,$designation->id))!!}" target="_blank">{!!URL::to('frontend/orderD',array(Session::get('client_id'),$designation->hysforms,$designation->id))!!}</a></h4>

                      </div>
                    </div>
	                          
	            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	                <div class="panel-heading">
	                    <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
	                    <div class="panel-actions">
	                            <span class="badge"></span>
	                    </div>
	                    <h3 class="panel-title">Edit {!!$designation->name!!}</h3>
	                </div><!-- /panel-heading -->
	                <div class="panel-body">

	{!! Form::model($designation) !!}
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
		
		<div class="form-group">
			{!! Form::label('info', 'Info') !!}
			{!! Form::text('info', $value = null, $attributes = array('class' => 'form-control','placeholder'=> "Enter some more about this designation, or leave blank.")) !!}
		</div>

		<div class="form-group">
			{!! Form::label('donation_amounts', 'Donation Amounts') !!}
			{!! Form::text('donation_amounts', $value = null, $attributes = array('class' => 'form-control','placeholder'=> "Enter something like: 10 or 10,100,200 or leave blank")) !!}
			{!! $errors->first('donation_amounts', '<p class="text-danger">:message</p>') !!}
		</div>

		<hr>

		<div class="form-group">
		{!! Form::label('hysform', 'Choose which donor form this designation will be used on') !!}

			{!! Form::select('hysform',$hysforms,$used_form,$attributes = array('class' => 'form-control')) !!}

		</div>
		
		<div class="form-group">
			{!! Form::label('emailset_id', 'Choose email set to use with this designation') !!}
			<select class="form-control" name="emailset_id">
				@foreach ($emailsets as $emailset) 
					@if ($emailset->id == $designation->emailset_id)
					<option value="{!! $emailset->id !!}" selected="seleceted">{!! $emailset->name !!}</option>
					@else
					<option value="{!! $emailset->id !!}">{!! $emailset->name !!}</option>
					@endif
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