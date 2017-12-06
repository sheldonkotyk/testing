<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url=URL::to('admin/add_designation');

$view_active='';
$view_url=URL::to('admin/all_designations');

$edit_active='';
if(isset($emailset))
  $edit_url=URL::to('admin/edit_emailset',array($emailset->id));
else
  $edit_url='#';

if($page=='all_designations')
{
  $view_active='active';
  $view_url='#';
}
if($page=='add_designation')
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
      <li><div class="btn-group"><a href="{!! $add_url !!}"> 
          <button type="button" class="btn btn-default {!!$add_active!!}">
             <span class="glyphicon glyphicon-plus"></span> Create Additional Gift
          </button></a></div></li>

    <li><div class="btn-group"><a href="{!!$view_url!!}"> 
        <button type="button" class="btn btn-default {!!$view_active!!}">
           <span class="glyphicon glyphicon-gift"></span> View All Additional Gifts
        </button></a></div></li>


       <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
              <button type="button" class="btn btn-default">
                 <span class="glyphicon glyphicon-question-sign"></span> About Additional Gifts
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
                  <h3 class="panel-title">About Additional Gifts</h3>
              </div><!-- /panel-heading -->
                  <div class="panel-body">
                    Additional gifts are for when a donor wants to add an additional amount of their choosing when they signup for sponsorship.<br><br>
                    Some examples of Additional Gifts:
                    <ul>
                    <li> Christmas Gifts for Students </li>
                    <li> Dormitory Repairs </li>
                    <li> School Supplies </li>
                    </ul>

                    <br>
                    You may add Additional Gifts to you programs by enabling 'Display Additional Gifts on order page' in the <a href="{!!URL::to('admin/settings')!!}">program settings</a>.

                    <hr>
                    You can also use the Share links to allow donors to contribute directly without being associated with a sponsorship program.
                    <br>

                    <hr>
                    Additional gifts are designed simply to be tacked onto a sponsorship program. <br> If you want to raise money specifically for one-time projects that need a total amount, you should <a href="http://help.helpyousponsor.com/read/funding_program_settings">look into creating a funding program</a>.<br>

                  </div>
                </div>
              </li>
          </ul>
