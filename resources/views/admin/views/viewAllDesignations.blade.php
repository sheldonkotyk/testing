@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	
	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Additional Gifts <small><span class="glyphicon glyphicon-gift"></span> View All Additional Gifts</small></h2>
	@include('admin.views.designationsMenu')

	<div class="app-body">
		 <div class="magic-layout">
	                          
	            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	                <div class="panel-heading">
	                    <div class="panel-icon"><i class="glyphicon glyphicon-gift"></i></div>
	                    <div class="panel-actions">
	                            <span class="badge">{!!count($designations)!!} Additional Gifts</span>
	                    </div>
	                    <h3 class="panel-title">All Additional Gifts</h3>
	                </div><!-- /panel-heading -->
	                <div class="panel-body">
	
						<table class="table">
							<thead>
								<th>Name</th>
								<th>Accounting Code</th>
								<th>Manage</th>
							</thead>
							<tbody>
							@if (!empty($designations))
							@foreach ($designations as $designation)
								<tr>
									<td><a href="{!! URL::to('admin/edit_designation', array($designation->id)) !!}"><span class="glyphicon glyphicon-pencil"></span> {!! $designation->name !!}</a></td>
									<td>{!! $designation->code !!}</td>
									<td><a href="{!! URL::to('admin/edit_designation', array($designation->id)) !!}"></a> <a href="{!! URL::to('admin/remove_designation', array($designation->id)) !!}"><span class="glyphicon glyphicon-remove"></span></a></td>
								</tr>
							@endforeach
							@else
								<tr>
									<td>No Additional Gifts to display.</td>
									<td></td>
									<td></td>
								</tr>
							@endif
							</tbody>
						</table>
					</div>
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