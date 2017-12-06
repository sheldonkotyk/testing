@extends('admin.default')

@section('headerscripts')
@stop

@section('content')

 
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

    <h1>
	<small><a href="{!!URL::to('admin/show_all_entities',array($program->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $program->name !!} </a></small>
	
	<div class="btn-group">
		<a href="{!! URL::to('admin/add_entity', array($program->id)) !!}">
		    <button type="button" class="btn btn-primary">
		        <span class="glyphicon glyphicon-plus"></span> Add Recipient
		    </button>
	    </a>
	</div>

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

	<h1>{!! $hysform->name !!} for {!! $name !!}</h1>


@include('admin.views.entityMenu')

	<div class="panel panel-default">
	<div class="panel-heading"><h3 class="panel-title">New Progress Report</h3></div>
		<div class="panel-body">
			<a class="btn btn-primary" href="{!! URL::to('admin/submit_form', array($type, $profile['id'], $program_id, $hysform->id)) !!}">Create New {!! $hysform->name !!}</a>
		</div>
	</div>
	
	@if (! $forms->isEmpty() )
	<div class="panel panel-default col-md-8">
		<div class="panel-heading"><h3 class="panel-title"><span class="icon ion-ios7-browsers"></span> Archived Progress Reports <span class="pull-right badge">{!!count($forms)!!} Report{!!(count($forms)>1 ? 's' : '')!!}</span></h3></div>
		<div class="panel-body">
			<ul class="list-group">
				@foreach ($forms as $form)
				<li class="list-group-item"><a href="{!! URL::to('admin/view_archived_form', array($form->id)) !!}">Submitted: {!! Carbon::createFromTimeStamp(strtotime($form->created_at))->toFormattedDateString() !!}</a></li>
				@endforeach
			</ul>
		</div>
	</div>
	@endif
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		
	});
	</script>
@stop