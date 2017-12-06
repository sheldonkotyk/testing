
<?php

$the_uri= Request::segments();
        $page= last($the_uri);

$add_active='';
$add_url=URL::to('admin/add_settings');

$view_active='';
$view_url=URL::to('admin/settings');

if($page=='settings')
{
	$view_active='active';
	$view_url='#';
}
if($page=='add_settings')
{
	$add_active='active';
	$add_url='#';
}

?>

	<ul class="nav nav-pills">
			<li><div class="btn-group"><a href="{!! $add_url !!}"> 
			    <button type="button" class="btn btn-default {!!$add_active!!}">
			       <span class="glyphicon glyphicon-plus"></span> Create Settings
			    </button></a></div></li>
		<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="glyphicon glyphicon-cog"></span> View All Settings
		    </button></a></div></li>

	     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> About Settings
	            </button></a></div></li>

				<li class="pull-right">
				<div id="collapseOne" class="panel panel-default panel-collapse collapse">
				<div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	               	<div class="panel-actions">
	                    	<div class="label label-success">Help Info</div>
	                </div>
	                <h3 class="panel-title">How Settings work</h3>
	            </div><!-- /panel-heading -->
		              <div class="panel-body">
		              Settings must be connected to a program before the program may be used.<br/>
		              If you are setting up multiple programs, you may configure multiple unique settings for each program.<br/>
		              Alternatively, you may use the same settings for multiple programs.</br>
		              <br/>
		              Settings determine what type of program it is, Contribution, Number of Sponsors, or Funding.<br/>
		              To learn more, simply click on the blue question mark next to each option.


		              
		              </div>
		            </div>
	            </li>
	        </ul>