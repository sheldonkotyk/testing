<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url=URL::to('admin/add_admin');

$view_active='';
$view_url=URL::to('admin/view_admins');

if($page=='view_admins')
{
	$view_active='active';
	$view_url='#';
}
if($page=='add_admin')
{
	$add_active='active';
	$add_url='#';
}

?>

<ul class="nav nav-pills">
		<li><div class="btn-group"><a href="{!! $add_url !!}"> 
		    <button type="button" class="btn btn-default {!!$add_active!!}">
		       <span class="glyphicon glyphicon-plus"></span> Create Admin
		    </button></a></div></li>

		<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="icon ion-person-stalker"></span> View All Admins
		    </button></a></div></li>

	     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> About Admins
	            </button></a></div></li>

				<li class="pull-right">
				<br>
				<div id="collapseOne" class="panel panel-default panel-collapse collapse">
				<div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	               	<div class="panel-actions">
	                    	<div class="label label-success">Info</div>
	                </div>
	                <h3 class="panel-title">About Admins</h3>
	            </div><!-- /panel-heading -->
		              <div class="panel-body">
		              
		              </div>
		            </div>
	            </li>
	        </ul>
