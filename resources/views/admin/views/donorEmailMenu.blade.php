<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url='';

$view_active='';
$view_url=URL::to('admin/email_manager');

$edit_active='';
$edit_url='';

if($page=='email_manager')
{
	$view_active='active';
	$view_url='#';
}
if($page=='add_form_field')
{
	$add_active='active';
	$add_url='#';
}
if($page=='edit_form')
{
	$edit_active='active';
	$edit_url='#';
}


?>


<ul class="nav nav-pills">
	<!-- <li><div class="btn-group"><a href="{!!$add_url!!}">
    <button type="button" class="btn btn-default {!!$add_active!!} disabled">
       <span class="glyphicon glyphicon-plus"></span> Compose Email
    </button></a></div></li> -->

	<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="glyphicon glyphicon-envelope"></span> View All Messages
		    </button></a></div></li>

	<li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
        <button type="button" class="btn btn-default">
           <span class="glyphicon glyphicon-question-sign"></span> About Message Manager
        </button></a></div></li>  

     

	<li class="pull-right">
	<div id="collapseOne" class="panel panel-default panel-collapse collapse">
	<div class="panel-heading">
        <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
       	<div class="panel-actions">
            	<div class="label label-success">Info</div>
        </div>
        <h3 class="panel-title">How Donor Messages Work</h3>
    </div><!-- /panel-heading -->
          <div class="panel-body">
         From the  <a href="{!!URL::to('admin/multi_program_select')!!}">frontend URL</a>, Donors may login and message their Recipients.<br/>
          All messages Donors send this way will arrive here, at the Message Manager.<br>
          Messages may be assigned to an Admin, who can in turn convey the message to the Recipient as well as send replies to the Donor.<br>
          <br>
          We Set things up this way to always keep an Admin between the Donor and the Recipient.<br>
          
          </div>
        </div>
    </li>
</ul>
