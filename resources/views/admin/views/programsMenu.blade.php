
<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_sub_active='';
$add_sub_url=URL::to('admin/add_sub_program');

$add_active='';
$add_url=URL::to('admin/add_child_program',array(Session::get('client_id')));

$view_active='';
$view_url=URL::to('admin/manage_program');


 $edit_program_active='';
 if(isset($program->id))
  $edit_program_url=URL::to('admin/edit_program',array($program->id));
 else
  $edit_program_url='#';

if($page=='manage_program')
{
  $view_active='active';
  $view_url='#';
}
if($page=='add_child_program')
{
  $add_active='active';
  $add_url='#';
}
if($page=='add_sub_program')
{
  $add_sub_active='active';
  $add_sub_url='#';
}
if($page=='edit_program')
{
  $edit_program_active='active';
  $edit_program_url='#';
}

?>

<ul class="nav nav-pills">

    @if($page!='program_settings'&&$page!='edit_program')
    <li><div class="btn-group"><a href="{!! $add_url !!}">
      <button type="button" class="btn btn-default {!!$add_active!!} ">
         <span class="glyphicon glyphicon-plus"></span> Create Program
      </button></a></div></li>
    <li><div class="btn-group"><a href="{!! $add_sub_url!!}">
      <button type="button" class="btn btn-default {!!$add_sub_active!!}">
         <span class="glyphicon glyphicon-plus"></span> Create Sub Program
      </button></a></div></li>
      @endif


    <li><div class="btn-group"><a href="{!! $view_url!!}">
      <button type="button" class="btn btn-default {!!$view_active!!}">
         <span class="icon ion-wrench"></span> View All Programs
      </button></a></div></li>


     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-question-sign"></span> About Programs
            </button></a></div></li>
      @if($page=='program_settings'||$page=='edit_program')
     <li class="pull-right"><div class="btn-group"><a href="{!!$edit_program_url!!}">
            <button type="button" class="btn btn-default {!!$edit_program_active!!}">
               <span class="glyphicon glyphicon-pencil"></span> Edit Program Details
            </button></a></div></li>
      @endif

			<li class="pull-right">
			<div id="collapseOne" class="panel panel-default panel-collapse collapse">
			<div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
               	<div class="panel-actions">
                    	<div class="label label-success">Info</div>
                </div>
                <h3 class="panel-title">How Programs work</h3>
            </div><!-- /panel-heading -->
	              <div class="panel-body">
	              For a program to be usable and active, it must have 4 components attached.<br/>
	              <h4>1. <span class="icon ion-ios7-gear"></span> Settings</h4><br/>
	              <h4>2. <span class="glyphicon glyphicon-list-alt"></span> Recipient Form</h4><br/>
	              <h4>3. <span class="glyphicon glyphicon-list-alt"></span> Donor Form</h4><br/>
	              <h4>4. <span class="glyphicon glyphicon-send"></span> Auto Emails</h4><br/>

	              <br/>
	              Optionally, you may also attach "Progress Report" forms to your program
	              </div>
	            </div>
            </li>
        </ul>