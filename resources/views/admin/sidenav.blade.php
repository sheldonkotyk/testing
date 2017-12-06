<?php

$the_uri= Request::segments();
if(isset($the_uri[1]))
    $page= $the_uri[1];
else
    $page='';

//Setup correct menu functionality for Admin Options menu
$admin_options_open='';

$account_active='';
$manage_admins_active='';
$admin_groups_active='';
$cc_active= '';
$email_settings = '';
$edit_template = '';

if($page=='edit_client_account')
    $account_active='active';
if($page=='update_client_cc')
    $cc_active= 'active';
if($page=='email_settings'||$page=='mailgun_logs')
    $email_settings='active';
if($page=='template')
    $edit_template='active';
if($page=='view_admins'||$page=='add_admin'||$page=='edit_admin')
    $manage_admins_active='active';
if($page=='view_groups'||$page=='create_group'||$page=='edit_group')
    $admin_groups_active='active';


if(!empty($account_active)||!empty($manage_admins_active)||!empty($admin_groups_active)||!empty($cc_active)||!empty($email_settings)||!empty($edit_template))
    $admin_options_open='open';


//Setup correct menu for Program Options
$_manage_program='';
$_forms='';
$_settings='';
$_email='';
$_all_designations='';
$_multi_program_select='';

$program_options_open='';

if($page=='manage_program'||$page=='add_child_program'||$page=='add_sub_program'||$page=='program_settings'||$page=='edit_program')
    $_manage_program='active';

if($page=='forms'||$page=='create_form'||$page=='manage_form'||$page=='edit_form'||$page=='add_form_field')
    $_forms='active';

if($page=='settings'||$page=='edit_settings'||$page=='add_settings')
    $_settings='active';

if($page=='email'||$page=='add_emailset'||$page=='edit_emailtemplate')
    $_email='active';

if($page=='all_designations'||$page=='add_designation'||$page=='edit_designation')
    $_all_designations='active';

if($page=='multi_program_select')
    $_multi_program_select='active';

if(!empty($_manage_program)||!empty($_forms)||!empty($_settings)||!empty($_email)||!empty($_all_designations)||!empty($_multi_program_select))
    $program_options_open='open';


$_donations='';
$_email_manager='';
$_archived_report='';

if($page=='donations'||$page=='view_donations')
    $_donations='active';

if($page=='email_manager'||$page=='view_email')
    $_email_manager='active';

if($page=='archived_report')
    $_archived_report='active';

?>

	<div class="side-header">
    	<!-- place your brand (recomended: dont change the id value) -->
   	 	<!-- (recomended: dont change the id value) -->
	    <h1 id="brand" class="brand">
        	<a href="{!! URL::to('admin') !!}">
            	{!!$org->organization!!}
	        </a>
        </h1><!-- /brand -->

        <!-- form search, remove class hide if you not place your brand -->
        <!-- (recomended: dont change the id value) -->
      <!--   <form id="smart-search" class="side-form hide" role="form">
            <input type="text" class="form-control" data-target=".side-wrapper" placeholder="Smart Finder">
        </form> --><!-- /side wrapper -->
    </div><!-- /side header -->
				<!-- Side Nav -->
	<div class="side-body">
		<nav class="side-nav">
			<ul>
				<ul >

				<?php

					
				?>
					
					@if (isset($permissions->admins)||isset($permissions->groups))
						<li class="side-nav-item">	
							<a href="#admin">
                                <i class="nav-item-caret"></i>
                                 <i class="nav-item-icon icon ion-person-stalker"></i>
                                Admin Options
                            </a>
						 <ul id="admin" class="side-nav-child {!!$admin_options_open!!}">
                                <li class="side-nav-item-heading">
                                    <a href="#" class="side-nav-back">
                                        <i class="nav-item-caret"></i>
                                        Admin Options
                                    </a>
                                </li><!-- /header layouts child -->
                                @if(isset($permissions->account))
                                <li class="side-nav-item {!!$account_active!!}">
                                    <a href="{!! URL::to('admin/edit_client_account') !!}">
                                        <i class="nav-item-icon icon ion-clipboard "></i>
                                        Account
                                    </a>
                                </li><!-- /variation -->
                                <li class="side-nav-item {!!$cc_active!!}">
                                    <a href="{!! URL::to('admin/update_client_cc') !!}">
                                        <i class="nav-item-icon glyphicon glyphicon-credit-card"></i>
                                        Update Credit Card
                                    </a>
                                </li><!-- /variation -->
                                 <li class="side-nav-item {!!$email_settings!!}">
                                    <a href="{!! URL::to('admin/email_settings') !!}">
                                        <i class="nav-item-icon icon ion-email"></i>
                                        Email Settings
                                    </a>
                                </li><!-- /variation -->
                                 <li class="side-nav-item {!!$edit_template!!}">
                                    <a href="{!! URL::to('admin/template') !!}">
                                        <i class="nav-item-icon icon ion-code"></i>
                                        Edit Template
                                    </a>
                                </li><!-- /variation -->
                                @endif
                                @if(isset($permissions->admins))
                                <li class="side-nav-item {!! $manage_admins_active!!}">
                                    <a href="{!! URL::to('admin/view_admins') !!}">
                                    	<span class="badge">{!!$number_of_admins!!}</span>
                                        <i class="nav-item-icon icon ion-person-stalker"></i>
                                        Admins
                                    </a>
                                </li><!-- /blank -->
                                @endif
                                @if(isset($permissions->groups))
                                <li class="side-nav-item {!! $admin_groups_active!!}">
                                    <a href="{!! URL::to('admin/view_groups') !!}">
                                    	<span class="badge">{!!$number_of_admin_groups!!}</span>
                                        <i class="nav-item-icon icon ion-ios7-people"></i>
                                        Groups
                                    </a>
                                </li><!-- /variation -->
                                @endif
                            </ul><!-- /layouts child -->
                        </li>
					@endif
					

					@if (isset($permissions->manage_settings)||isset($permissions->forms)||isset($permissions->manage_programs)||isset($permissions->manage_designations))
						<li class="side-nav-item">	
							<a href="#program">
                                <i class="nav-item-caret"></i>
                                 <i class="nav-item-icon icon ion-ios7-cog"></i>
                                Program Options
                            </a>
						 <ul id="program" class="side-nav-child {!!$program_options_open!!}">
                                <li class="side-nav-item-heading">
                                    <a href="#" class="side-nav-back">
                                        <i class="nav-item-caret"></i>
                                        Program Options
                                    </a>
                                </li><!-- /header layouts child -->
                                @if(isset($permissions->manage_programs))
                                <li class="side-nav-item {!! $_manage_program!!}">
                                    <a href="{!! URL::to('admin/manage_program') !!}">
                                    	<span class="badge">{!!$number_of_programs!!}</span>
                                        <i class="nav-item-icon icon ion-wrench"></i>
                                        Programs
                                    </a>
                                </li><!-- /variation -->
                                @endif
                                @if(isset($permissions->forms))
                                <li class="side-nav-item {!! $_forms!!}">
                                    <a href="{!! URL::to('admin/forms') !!}">
                                        <i class="nav-item-icon icon ion-social-buffer"></i>
                                        Forms
                                    </a>
                                </li><!-- /variation -->
                                @endif
                                 @if(isset($permissions->manage_settings))
                                <li class="side-nav-item {!!$_settings!!}">
                                    <a href="{!! URL::to('admin/settings') !!}">
                                        <i class="nav-item-icon icon ion-ios7-gear"></i>
                                        Settings
                                    </a>
                                </li><!-- /blank -->
                                @endif
                                @if(isset($permissions->manage_email))
                                <li class="side-nav-item {!! $_email!!}">
                                    <a href="{!! URL::to('admin/email') !!}">
                                       <i class="nav-item-icon glyphicon glyphicon-send"></i>
                                        Auto Emails
                                    </a>
                                </li><!-- /variation -->
                                @endif
                                 @if(isset($permissions->manage_designations))
                                <li class="side-nav-item {!! $_all_designations!!}">
                                    <a href="{!! URL::to('admin/all_designations') !!}">
                                        <i class="nav-item-icon glyphicon glyphicon-gift"></i>
                                        Additional Gifts
                                    </a>
                                </li><!-- /variation -->
                                @endif

                                @if(isset($permissions->manage_programs))
                                    <li class="side-nav-item {!! $_multi_program_select!!}">
                                        <a href="{!! URL::to('admin/multi_program_select') !!}">
                                            <i class="nav-item-icon glyphicon glyphicon-th"></i>
                                            URL Generator
                                        </a>
                                    </li><!-- /variation -->
                                @endif
                            </ul><!-- /layouts child -->
                        </li>
					@endif

				</ul>
	
				<li class="divider"></li>
	
				@if (isset($permissions->email_manager) && $permissions->email_manager == 1)
					<li class="side-nav-item {!!$_email_manager!!}"><a href="{!! URL::to('admin/email_manager') !!}"> <i class="nav-item-icon icon ion-ios7-email"></i>  Message Manager <span class="badge">{!!$new_emails!!}</span></a></li>
				@endif
				
				@if (isset($permissions->donations) && $permissions->donations == 1)
					<li class="side-nav-item {!!$_donations!!}"><a href="{!! URL::to('admin/donations') !!}"> <i class="nav-item-icon icon ion-ios7-calculator"></i>  Donations</a></li>
				@endif
				
				@if (isset($permissions->form_report) && $permissions->form_report == 1)
					<li class="side-nav-item {!!$_archived_report!!}"><a href="{!! URL::to('admin/archived_report') !!}">  <i class="nav-item-icon icon ion-ios7-browsers"></i> Progress Reports</a></li>
				@endif
			</ul>
			</nav>
		</nav>
	</div>
