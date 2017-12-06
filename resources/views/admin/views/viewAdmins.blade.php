@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif


<h2>{!!Client::find(Session::get('client_id'))->organization!!} Admins<small> <span class="icon ion-person-stalker"></span> All Admins</small></h2>

@include('admin.views.adminsMenu')

<!-- <p><a href="{!! URL::to('admin/add_admin') !!}" class="btn btn-default">Add Admin <span class="glyphicon glyphicon-plus"></span></a></p> -->

<div class="app-body">
<div class="magic-layout">
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-person-stalker"></i></div>
                <div class="panel-actions">
                    <div class="badge">{!!count($admins)!!} Admins</div>
                </div>
               
                <h3 class="panel-title">All Admins</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

<table class="table table-striped">
	<tr>
		<th>First Name</th>
		<th>Last Name</th>
		<th>Email Address</th>
		<th>Admin Group</th>
		<th>Last Login</th>
		<th>Edit</th>
	</tr>
@foreach ($admins as $admin)
	<?php $g = 'group-'.$admin->group_id; ?>
	@if (isset($permissions->$g) && $permissions->$g == 1 || isset($permissions->group_all) && $permissions->group_all == 1)
		@if ( $admin->email != 'support@helpyousponsor.com' )
			<tr>
				<td>{!! $admin->first_name !!}</td>
				<td>{!! $admin->last_name !!}</td>
				<td>{!! $admin->email !!}</td>
				
				@if(isset($admin->group))
					<td>{!! $admin->group->name !!}</td>
				@else
					<td></td>
				@endif
		
				@if ($admin->last_login != null)
				<td>{!! Carbon::createFromTimeStamp(strtotime($admin->last_login))->diffForHumans() !!}</td>
				@else
				<td>Never</td>
				@endif
				<td><a href="{!! URL::to('admin/edit_admin', array($admin->id)) !!}"><span class="glyphicon glyphicon-pencil"></span> Edit</a> 
					@if ($admin->activation_code != null)
					<a class="btn btn-xs btn-default" href="{!! URL::to('admin/manual_account_activation', array($admin->id)) !!}">Activate Admin</a>
					@endif
				</td>
			</tr>
		@endif
	@endif
@endforeach

</table>
</div>
</div>
</div>
</div>
@stop

@section('footerscripts')
@stop