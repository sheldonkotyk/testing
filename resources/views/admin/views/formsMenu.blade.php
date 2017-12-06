<?php

$the_uri= Request::segments();
        $page= last($the_uri);

$add_active='';
$add_url=URL::to('admin/create_form');

$view_active='';
$view_url=URL::to('admin/forms');

if($page=='forms')
{
	$view_active='active';
	$view_url='#';
}
if($page=='create_form')
{
	$add_active='active';
	$add_url='#';
}

?>


<ul class="nav nav-pills">
		@if (isset($permissions->new_form) && $permissions->new_form == 1)
			<li><div class="btn-group"><a href="{!! $add_url !!}"> 
			    <button type="button" class="btn btn-default {!!$add_active!!}">
			       <span class="glyphicon glyphicon-plus"></span> Create Form
			    </button></a></div></li>
		@endif

		<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="icon ion-social-buffer"></span> View All Forms
		    </button></a></div></li>

	     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> About Forms
	            </button></a></div></li>

				<li class="pull-right">
				<br>
				<div id="collapseOne" class="panel panel-default panel-collapse collapse">
				<div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	               	<div class="panel-actions">
	                    	<div class="label label-success">Info</div>
	                </div>
	                <h3 class="panel-title">About Forms</h3>
	            </div><!-- /panel-heading -->
		              <div class="panel-body">
		              Forms define the data you will store for both Recipients and Donors.

		              <h4>Form Types</h4>
					<ul>
						<li><strong>Recipient Profile</strong> - Use this form type for creating the profile for your sponsorship recipient. If you are sponsoring children, this could be called the child profile. </li>
						<li><strong>Donor Profile</strong> - Use this form type for creating profiles for your donors or sponsors.</li>
						<li><strong>Progress Report</strong> - Use this form type for creating forms for collecting information about your recipients or donors. When you have information you want to archive this is the form you will use. For example, this form could be used as a contact record for donors or progress reports for children in a child sponsorship program.</li>
					</ul>
		              </div>
		            </div>
	            </li>
	        </ul>
