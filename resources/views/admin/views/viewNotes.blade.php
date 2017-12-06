@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/typeahead.js-bootstrap.css') !!}
	<script>
	$(document).ready(function() {
		$('input#categories').typeahead({
		  name: 'categories',
		  prefetch: {
		  	url: '{!! URL::to('admin/list_cat') !!}/{!! $type !!}/{!! $program_id !!}',
		  	ttl: 1
		  }
		});
	});
	</script>
@stop

@section('content')

<?php 
$profile['id']=$id;
?>

@if($type=='entity')
<h1>
<small><a href="{!!URL::to('admin/show_all_entities',array($program->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $program->name !!} </a></small>

<div class="btn-group"><a href="{!! URL::to('admin/add_entity', array($program->id)) !!}">
            <button type="button" class="btn btn-primary">
               <span class="glyphicon glyphicon-plus"></span> Add Recipient
            </button></a></div>
            <div class="pull-right">
            <small>Share:</small>
                <a class="btn btn-xs btn-default btn-extend be-left" href="https://twitter.com/share?url={!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}&text=Meet {!!$name!!}:" target="_blank">
                        <i class="icon ion-social-twitter"></i>Tweet</a> 
                 <a class="btn btn-xs btn-default btn-extend be-left" href="https://www.facebook.com/sharer/sharer.php?u={!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}&display=popup" target="_blank">
                        <i class="icon ion-social-facebook"></i>Share</a> 
                 <a class="btn btn-xs btn-default btn-extend be-left" href="mailto:?subject=Meet%20{!!$name!!}&body=Click%20on%20the%20link%20to%20find%20out%20about%20{!!$name!!}%0D%0A{!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}">
                         Email
                          <i class="glyphicon glyphicon-envelope"></i>
                         </a>
                    <a class="btn btn-xs btn-default btn-extend be-left" data-toggle="collapse" href="#collapseTwo">
                        <i class="glyphicon glyphicon-link"></i> Embed</a>
            </div>
</h1>
@endif

@if($type=='donor')
<h1><small><a href="{!!URL::to('admin/show_all_donors',array($hysform->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $hysform->name !!} </a></small>
	<div class="btn-group"><a href="{!! URL::to('admin/add_donor', array($hysform->id)) !!}">

            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-plus"></span> Add Donor
            </button></a></div>
	</h1>
@endif

<h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $name !!} <small><span class="icon ion-android-note"></span> <em>Notes</em>@if((isset($donor['deleted_at']) && $donor['deleted_at'] != null) || (isset($entity['deleted_at']) && $entity['deleted_at'] != null)) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small>
</h1>

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

@if($type=='entity')
	@include('admin.views.entityMenu')
@endif
@if($type=='donor')
	@include('admin.views.donorMenu')
@endif

<div class="app-body">
    <!-- app content here -->
    <div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
            	<div class="panel-actions">
		                <span class="badge">{!!count($notes)!!} Notes </span>
		        </div>
                <div class="panel-icon"><i class="icon ion-android-note"></i></div>
                <h3 class="panel-title">{!!$name!!}'s Notes</h3>

            </div><!-- /panel-heading -->
            <div class="panel-body">

	<div class="col-md-8">
		{!! Form::open(array('url' => 'admin/add_note/'.$type.'/'.$program_id.'', 'class' => 'reverse-well')) !!}
		<div class="form-group">
			{!! Form::textarea('note', '', array('placeholder' => 'Enter your note', 'class' => 'form-control', 'rows' => '2')) !!}
		</div>
		{!! $errors->first('note', '<p class="text-danger">:message</p>') !!}

		<div class="form-group">
			{!! Form::label('categories', 'Category') !!}
			<div class="input-group">
				{!! Form::text('categories', '', array('placeholder' => 'Choose a category', 'class' => 'form-control')) !!}
			</div>
		</div>
			{!! Form::hidden('entity_id', $id) !!}
			{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
		{!! Form::close() !!}
	<hr>
	<h2>Notes</h2>
	@foreach ($notes as $note) 
		<div class="alert alert-warning">
			<a href="{!! URL::to('admin/edit_note', array($note->id, $program_id)) !!}" title="Edit"><span class="glyphicon glyphicon-edit pull-right"></span></a>
			<p>{!! $note->note !!}</p>
			<p><small>Created: {!! $note->created_at !!} </small>
			@if ($note->created_at != $note->updated_at)
				: <small>Updated: {!! $note->updated_at !!}</small>
			@endif
			</p>
			@foreach ($categories as $category)
				@if ($category->id == $note->category_id)
					<small>Category:</small> <span class="label label-info">{!! $category->category !!}</span>
				@endif
			@endforeach
		</div>
	@endforeach
	</div>
	<div class="col-md-4">
		<h6 class="text-muted">VIEW BY CATEGORY</h6>
		<ul class="nav nav-pills nav-stacked">
			<li><a href="{!! URL::to('admin/notes', array($id, $type, $program_id)) !!}">All</a></li>
			@if (!empty($categories))
				@foreach ($categories as $category)
					@if ($cat == $category->id)
						<li class="active">
					@else
						<li>
					@endif
					<a href="{!! URL::to('admin/notes', array($id, $type, $program_id, $category->id)) !!}">{!! $category->category !!}</a></li>
				@endforeach
			@endif
		</ul>
	</div>
</div>
</div>
</div>
@stop

@section('footerscripts')
	{!! HTML::script('js/typeahead.min.js') !!}
<script>
$(document).ready(function() {
});
</script>
@stop