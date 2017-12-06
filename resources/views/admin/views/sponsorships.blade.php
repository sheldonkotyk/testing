@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/chosen.min.css') !!}
	<script>
	$(document).ready(function() {
		$(".entities").chosen({
		    disable_search_threshold: 10,
		    no_results_text: "Oops, nothing found!",
		    width: "95%"
		  });	
	});
	</script>
@stop

@section('content')

<h1><small><a href="{!!URL::to('admin/show_all_donors',array($hysform->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $hysform->name !!} </a></small>
	<div class="btn-group"><a href="{!! URL::to('admin/add_donor', array($hysform->id)) !!}">

            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-plus"></span> Add Donor
            </button></a></div>
	</h1>
	<h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $name !!} <small><span class="glyphicon glyphicon-link"></span> <em>Sponsorships</em>@if(isset($donor)&&$donor['deleted_at']!=null) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small></h1>
	
	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif
	@include('admin.views.donorMenu')
<div class="app-body">
			<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
	               	<div class="panel-actions">
	                    	<span class="badge">{!!count($sponsorships)!!}</span>
	                </div>
	                <h3 class="panel-title">Sponsorships for {!!$name!!}</h3>
	            </div><!-- /panel-heading -->
	            <div class="panel-body ">
					{!! Form::open() !!}
					<div class="list-group">
						<div class="list-group-item">
							<div class="list-group-item-heading">

								<h4><i class="glyphicon glyphicon-plus"></i> Add Sponsorship to {!!$name!!}</h4>
							</div>
							<div class="form-group">
								<label for="entities"></label>
								<select name="entities" class="entities form-control" data-placeholder="Select One to Add">
									<option value></option>
								@foreach ($entities as $entity)
									<option value="{!! $entity['id'] !!}">{!! $entity['name'] !!}</option>
								@endforeach
								</select>
								{!! $errors->first('entities', '<p class="text-danger">:message</p>') !!}
								<p class="help-block">Once you have made your selection click save to create the sponsorship relationship.</p>
							</div>

							{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
							{!! Form::close() !!}
						</div>
					</div>
				</div>
	
		<div class="panel-body">
	@if (!empty($sponsorships))

	<h3>Current Sponsorships</h3>
	<table class="table table-striped">
		<tr>
			<th>Name</th>
			<th>Created</th>
			<th>Remove</th>
		</tr>
	@foreach ($sponsorships as $sponsorship)
		<tr>
			<td><a href="{!! URL::to('admin/edit_entity', array($sponsorship['id'])) !!}">{!! $sponsorship['name'] !!}</a>
			 <a class="btn btn-default btn-xs" href="{!!URL::to('admin/send_message',array($sponsorship['entity_id'],$donor->id,'donor',null))!!}">Compose Message</a>
			@if(empty($sponsorship['commitment_id']))
				<span class="text-danger">Error: Sponsorship with no Commitment.</span>
			@endif
			@if($sponsorship['deleted']=='1')
			<span class="text-danger">Error: Entity has been Archived for this sponsorship.</span>
			@endif
			</td>
			<td>{!! $sponsorship['created'] !!}</td>
			<td><a href="{!! URL::to('admin/remove_sponsorship', array($sponsorship['donor_entity_id'])) !!}"><span class="glyphicon glyphicon-remove"></span></a></td>
		</tr>
	@endforeach
	</table>
	@else
		<br><br><br><br><br><p>No Current Sponsorships</p>
	@endif
	
	@if (!empty($archived))
	<h4 class="text-muted">Archived Sponsorships</h4>
	<table class="table table-striped">
		<tr>
			<th class="text-muted">Name</th>
			<th class="text-muted">Created</th>
			<th class="text-muted">Archived</th>
			<th class="text-muted">Restore</th>
		</tr>
	@foreach ($archived as $a)
		@if (isset($a['id']))
		<tr>
			<td><a class="text-muted" href="{!! URL::to('admin/edit_entity', array($a['id'])) !!}">{!! $a['name'] !!}</a></td>
			<td class="text-muted">{!! $a['created'] !!}</td>
			<td class="text-muted">{!! $a['deleted'] !!}</td>
			<td><a class="text-muted" href="{!! URL::to('admin/restore_sponsorship', array($a['donor_entity_id'])) !!}"><span class="glyphicon glyphicon-repeat"></span></a></td>
		</tr>
		@endif
	@endforeach
	</table>
	@endif
	</div>
	</div>
	</div>
@stop

@section('footerscripts')
	{!! HTML::script('js/chosen.jquery.min.js') !!}
@stop