<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url=URL::to('admin/add_form_field',array($hysform->id, $hysform->type));

$view_active='';
$view_url=URL::to('admin/manage_form',array($hysform->id));

$edit_active='';
$edit_url=URL::to('admin/edit_form',array($hysform->id));

if($page=='manage_form')
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
	<li><div class="btn-group"><a href="{!!$add_url!!}">
    <button type="button" class="btn btn-default {!!$add_active!!}">
       <span class="glyphicon glyphicon-plus"></span> Add New Field
    </button></a></div></li>

	<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="icon ion-navicon-round"></span> Manage Fields
		    </button></a></div></li>
		    
    <li><div class="btn-group"><a href="{!!URL::to('admin/forms')!!}"> 
	    <button type="button" class="btn btn-default">
	       <span class="icon ion-social-buffer"></span> View All Forms
	    </button></a></div></li>

	<li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
        <button type="button" class="btn btn-default">
           <span class="glyphicon glyphicon-question-sign"></span> About Fields
        </button></a></div></li>

	<li class="pull-right"><div class="btn-group"><a href="{!! $edit_url !!}">
    <button type="button" class="btn btn-default {!!$edit_active!!}">
       <span class="glyphicon glyphicon-pencil"></span> Edit Form Details
    </button></a></div></li>

   

     

	<li class="pull-right">
	<div id="collapseOne" class="panel panel-default panel-collapse collapse">
	<div class="panel-heading">
        <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
       	<div class="panel-actions">
            	<div class="label label-success">Info</div>
        </div>
        <h3 class="panel-title">How Form Fields work</h3>
    </div><!-- /panel-heading -->
          <div class="panel-body">
          Each Form can contain as many fields as you need.<br/>
          <h4>Available Field Types</h4>
          <ul>
            <li>Text: You will want to use this for short text fields (less than 100 characters). This field is good for storing names.</li>
            <li>Textarea: This is for storing more lengthy text, such as a detailed profile. This can also</li>
            <li>Static Text: This is for adding un-alterable text that will be the same for all Recipients or Donors. This is useful for inputting information that will be the same for a whole program. Then if you need to change it later, you just change it in the form itself, rather than changing each entity individually.</li>
            <li>Age: This field is a date field that allows you to input a birthdate and it will display the date and age automatically, like so: "Age: 32 (Mar 3, 1982)"</li>
            <li>Date: This is a simple date field.</li>
            <li>Link: This can be used to set a weblink, If you had a video of a project for funding, it could be displayed using this.</li>
            <li>Select List: This can be used for sorting. The format with this is to type in a number of different options separated by commas. This generally works better when spaces aren't used. </li>
            <li>Checkbox: This is for creating a simple yes or no option.</li>
            <li>Table: This can be used to create a table to store information. The format is to make a list of table columns separated by commas.</li>
            <li>Auto Increment ID: If you want to create a unique ID for each entity that will automatically increment when new entities are created, use this field.</li>
          
          </ul>

          
          </div>
        </div>
    </li>
</ul>
