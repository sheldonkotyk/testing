@extends('admin.default')
@section('content')
	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Forms <small> <span class="icon ion-social-buffer"></span> View All Forms</small></h2>
	
	@include('admin.views.formsMenu')
	
	<div class="app-body">
		 <div class="magic-layout">
	                          
	            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	                <div class="panel-heading">
	                    <div class="panel-icon"><i class="icon ion-social-buffer"></i></div>
	                    <div class="panel-actions">
	                            <span class="badge">{!!count($hysforms)!!} Forms</span>
	                    </div>
	                    <h3 class="panel-title">All Forms</h3>
	                </div><!-- /panel-heading -->
	                <div class="panel-body">
						
						<table class="table table-striped">
							<tr>
								<th>Form Name</th>
								<th>Type</th>
								<th>Created</th>
								<th>Edit</th>
							</tr>
							@foreach ($hysforms as $form) 
							<tr>
								<td><a href="{!! URL::to('admin/manage_form', array($form->id)) !!}">{!! $form->name !!}</a></td>
								@if ($form->type == 'entity') 
									<td>Recipient Profile</td>
								@elseif ($form->type == 'donor')
									<td>Donor Profile</td>
								@elseif ($form->type == 'submit')
									<td>Progress Report</td>
								@endif
								<td>{!! Carbon::createFromTimeStamp(strtotime($form->created_at))->toFormattedDateString() !!}</td>
								<td><a href="{!! URL::to('admin/manage_form', array($form->id)) !!}"><span class="icon ion-navicon-round"></span></a></td>
							</tr>
							@endforeach
						</table>
					</div>
				</div>
			</div>
	</div>

@stop
@section('footerscripts')
	<script>
	$(document).ready(function() {

		$("#manage_forms").collapse('show')
	});
	</script>
@stop