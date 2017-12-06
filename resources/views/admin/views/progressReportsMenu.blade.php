<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url=URL::to('admin/add_emailset');

$view_active='';
$view_url=URL::to('admin/archived_report');

$edit_active='';
if(isset($emailset))
	$edit_url=URL::to('admin/edit_emailset',array($emailset->id));
else
	$edit_url='#';

if(isset($programs))
{
	$view_active='active';
	$view_url='#';
}
if($page=='add_emailset')
{
	$add_active='active';
	$add_url='#';
}

if($page=='edit_emailset')
{
	$edit_active='active';
	$edit_url='#';
}


?>


<ul class="nav nav-pills">
		

		<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="icon ion-ios7-browsers"></span> View Progress Reports
		    </button></a></div></li>


	     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> About Progress Reports
	            </button></a></div></li>
	     @if($page=='edit_emailset'||$page=='edit_emailtemplate')
		     <li class="pull-right"><div class="btn-group"><a href="{!!$edit_url!!}">
		            <button type="button" class="btn btn-default {!!$edit_active!!}">
		               <span class="glyphicon glyphicon-pencil"></span> Edit Details
		            </button></a></div></li>
	     @endif

				<li class="pull-right">
				<br>
				<div id="collapseOne" class="panel panel-default panel-collapse collapse">
				<div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	               	<div class="panel-actions">
	                    	<div class="label label-success">Info</div>
	                </div>
	                <h3 class="panel-title">About Progress Reports</h3>
	            </div><!-- /panel-heading -->
		              <div class="panel-body">
		              	If you have a Progress Report form connected to your Donors or Recipients, this page allows you to view them all at once.
		              </div>
		            </div>
	            </li>
	        </ul>
