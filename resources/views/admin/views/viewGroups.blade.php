@extends('admin.default')

@section('headerscripts')
@stop

@section('content')

<h2>{!!Client::find(Session::get('client_id'))->organization!!} Groups<small> <span class="icon ion-ios7-people"></span> All Groups</small></h2>

@include('admin.views.adminGroupsMenu')

<div class="app-body">
<div class="magic-layout">
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-ios7-people"></i></div>
                <div class="panel-actions">
                    <div class="badge">{!!count($groups)!!} Groups</div>
                </div>
               
                <h3 class="panel-title">All Groups</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">


<table class="table table-striped">
	<thead>
	<tr>
		<th>Name</th>
		<th>Remove</th>
	</tr>
	</thead>
	<tbody>
@foreach ($groups as $group)
	<?php $g = 'group-'.$group->id; ?>
	@if (isset($permissions->$g) && $permissions->$g == 1 || isset($permissions->group_all) && $permissions->group_all == 1)
	<tr>
		<td><a href="{!! URL::to('admin/edit_group') !!}/{!! $group->id !!}"><span class="glyphicon glyphicon-pencil"></span> {!! $group->name !!}</a></td>
		<td><a href="{!! URL::to('admin/remove_group') !!}/{!! $group->id !!}"><span class="glyphicon glyphicon-remove"></span></a></td>
	</tr>
	@endif
@endforeach
	</tbody>
</table>

</div>
</div>
</div>
</div>
@stop

@section('footerscripts')
@stop