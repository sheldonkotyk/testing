@extends('admin.default')

@section('headerscripts')
@stop

@section('content')

<h2>{!!Client::find(Session::get('client_id'))->organization!!} Groups<small> <span class="glyphicon glyphicon-plus"></span> Create Group</small></h2>

	@include('admin.views.adminGroupsMenu')
	<div class="app-body">
		<div class="magic-layout">
			<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
	                <div class="panel-actions">
	                    <div class="label label-success">New Group</div>
	                </div>
	               
	                <h3 class="panel-title">Create Group</h3>
	            </div><!-- /panel-heading -->
	            <div class="panel-body">

					{!! Form::open() !!}
					    <div class="form-group">
					        {!! Form::label('name', 'Enter Group Name') !!}
					        {!! Form::text('name', $value = null, array('placeholder' => 'Minimum of 3 characters', 'class' => 'form-control')) !!}
							{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
					    </div>
					    
					    <h6>Settings</h6>
					   
						@if (isset($permissions->groups) && $permissions->groups == 1)
						<div class="row">
							<div class="col-md-12">	
							    <div class="checkbox">
									<label>
										{!! Form::checkbox('groups', '1') !!}
										Access to create and edit Groups
									</label>
								</div>
							</div>
							
							<div class="col-md-offset-1 clearfix">
								<h6>Access to specific admin groups</h6>
								<p><small>To have access to edit a group the admin must have access to Admin Groups above</small></p>
									@if (isset($permissions->group_all) && $permissions->group_all == 1)
									<label>
										{!! Form::checkbox('group_all', '1') !!}
										Access to all groups
									</label>
									@endif
									
								@foreach ($all_groups as $group)
									<?php $g = 'group-'.$group->id; ?>

									@if (isset($permissions->$g) && $permissions->$g == 1 || isset($permissions->group_all) && $permissions->group_all == 1)
									<div class="checkbox">
										<label>
											{!! Form::checkbox($g, '1') !!}
											Access to group: <b>{!! $group->name !!}</b>
										</label>
									</div>
									@endif
								@endforeach	
							</div>	
						</div>
						@endif
					    
						@if (isset($permissions->admins) && $permissions->admins == 1)
					    <div class="checkbox">
							<label>
								{!! Form::checkbox('admins', '1') !!}
								Access to create and edit Admins
							</label>
						</div>
						@endif
					
						@if (isset($permissions->forms) && $permissions->forms == 1)
					    <div class="checkbox">
							<label>
								{!! Form::checkbox('forms', '1') !!}
								Access to edit and remove forms for programs and donors
							</label>
						</div>
						@endif

						@if (isset($permissions->new_form) && $permissions->new_form == 1)
					    <div class="checkbox">
							<label>
								{!! Form::checkbox('new_form', '1') !!}
								Access to create new forms for programs and donors
							</label>
						</div>
						@endif
					
						@if (isset($permissions->manage_programs) && $permissions->manage_programs ==1)
					    <div class="checkbox">
							<label>
								{!! Form::checkbox('manage_programs', '1') !!}
								Access to add and edit programs and program settings
							</label>
						</div>
						@endif
						
						@if (isset($permissions->manage_settings) && $permissions->manage_settings == 1) 
						<div class="checkbox">
							<label>
								{!! Form::checkbox('manage_settings', '1') !!}
								Access to create and edit program settings
							</label>
						</div>
						@endif
						
						@if (isset($permissions->manage_designations) && $permissions->manage_designations ==1)
						<div class="checkbox">
							<label>
								{!! Form::checkbox('manage_designations', '1') !!}
								Access to setup additional gifts
							</label>
						</div>
						@endif

						@if (isset($permissions->manage_email) && $permissions->manage_email == 1) 
						<div class="checkbox">
							<label>
								{!! Form::checkbox('manage_email', '1') !!}
								Access to setup email templates
							</label>
						</div>
						@endif
						
						@if (isset($permissions->email_manager) && $permissions->email_manager ==1)
						<div class="checkbox">
							<label>
								{!! Form::checkbox('email_manager', '1') !!}
								Access to the email manager
							</label>
						</div>
						@endif
						
						@if (isset($permissions->donations) && $permissions->donations ==1)
						<div class="checkbox">
							<label>
								{!! Form::checkbox('donations', '1') !!}
								Access to donations
							</label>
						</div>
						@endif
						
						<hr>
						<h6>Programs</h6>
						<p class="lead">NOTE: If you have nested programs then permissions need to be given for the parent program in order for an admin to access a child program.</p>

						<hr>
						<div class="checkbox">
							<label>
								{!! Form::checkbox('disable_entity_archive', '1') !!}
								Disable Archiving of Recipients
							</label>
						</div>
						<div class="checkbox">
							<label>
								{!! Form::checkbox('disable_entity_restore', '1') !!}
								Disable Restoring of Recipients
							</label>
						</div>
						<div class="checkbox">
							<label>
								{!! Form::checkbox('disable_entity_delete', '1') !!}
								Disable Permanent Deletion of Recipients
							</label>
						</div>
						<hr>

						@foreach ($programs as $program)
						<?php $p = 'program-'.$program->id; ?>
						@if (isset($permissions->$p) && $permissions->$p == 1)
					    <div class="checkbox">
							<label>
								{!! Form::checkbox('program-'.$program->id.'', '1') !!}
								{!! $program->name !!}
							</label>
						</div>
						@endif
						@endforeach
						
						<hr>
						<h6>Donors</h6>

						<hr>
						<div class="checkbox">
							<label>
								{!! Form::checkbox('disable_donor_archive', '1') !!}
								Disable Archiving of Donors
							</label>
						</div>
						<div class="checkbox">
							<label>
								{!! Form::checkbox('disable_donor_restore', '1') !!}
								Disable Restoring of Donors
							</label>
						</div>
						<div class="checkbox">
							<label>
								{!! Form::checkbox('disable_donor_delete', '1') !!}
								Disable Permanent Deletion of Donors
							</label>
						</div>
						<hr>

						@foreach ($donors as $donor)
						<?php $d = 'donor-'.$donor->id; ?>
						@if (isset($permissions->$d) && $permissions->$d == 1)
					    <div class="checkbox">
							<label>
								{!! Form::checkbox('donor-'.$donor->id.'', '1') !!}
								Manage Donors in - {!! $donor->name !!}
							</label>
						</div>
						@endif
						<hr>
						@endforeach
					
					{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
					<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
					{!! Form::close() !!}
					</div>
				</div>
			</div>
		</div>

@stop

@section('footerscripts')
@stop