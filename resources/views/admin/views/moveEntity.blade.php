@extends('admin.default')

@section('headerscripts')
@stop

@section('content')

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
<h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $name !!} <small><span class="icon ion-arrow-right-a"></span> <em>Move</em> @if($entity['deleted_at']!=null) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small></small>
	
</h1>

	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif
	
	@include('admin.views.entityMenu')

<div class="app-body">
    <!-- app content here -->
    <div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-arrow-right-a"></i></div>
                <h3 class="panel-title">Move {!!$name!!} to another program</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">


	@if(empty($compatible_programs))

		@if($count==0)
		<p class='help-text'>There are no programs that share the same sponsorship form. </p>
		@else
		<p class='help-text'>You don't have permissions to any compatible programs.</p>
		@endif

	@else
	Choose Program and click "Move"
	<div class='form-group'>
	{!! Form::open() !!}
	{!! Form::select('new_program',$compatible_programs,null,array('class'=>'form-control')) !!}
	<br/>
	{!! Form::submit('Move',array('class'=>'btn btn-primary'))!!}
	{!! Form::close()!!}
	</div>
	<p class='help-text'>You may only move recipients to programs that have the same Sponsorship form. You also must have the correct permissions.</p>
	@endif
</div>
@stop

@section('footerscripts')
<script>

</script>
@stop