
<?php
if(!isset($trashed))
	$disabled='disabled="disabled"';
else
	$disabled='';
?>

<ul class="nav nav-pills">

            @if(isset($trashed))
                <li class=""><div class="btn-group"><a href="{!! URL::to('admin/add_donor', array($hysform_id)) !!}">
                <button type="button" class="btn btn-default">
                   <span class="glyphicon glyphicon-plus"></span> Add Donor
                </button></a></div></li>
            @else
                <li class=""><div class="btn-group"><a href="#">
                <button type="button" class="btn btn-default active">
                   <span class="glyphicon glyphicon-plus"></span> Add Donor
                </button></a></div></li>
            @endif

            @if(isset($trashed)&&$trashed=='')
              @if(!isset($permissions->disable_donor_archive)||$permissions->disable_donor_archive!='1')
                <li class=""><div class="btn-group">
                <button type="button" class="btn btn-default" id="archive">
                   <span class="glyphicon glyphicon-trash"></span> Archive Selected
                </button></div></li>
              @endif
            @endif
            @if(isset($trashed)&&$trashed=='1')
                @if(!isset($permissions->disable_donor_restore)||$permissions->disable_donor_restore!='1')
                  <li class=""><div class="btn-group">
                  <button type="button" class="btn btn-default" id="restore">
                     <span class="glyphicon glyphicon-repeat"></span> Restore Selected
                  </button></div></li>
                @endif
                @if(!isset($permissions->disable_donor_delete)||$permissions->disable_donor_delete!='1')
                  <li class=""><div class="btn-group">
                  <button type="button" class="btn btn-danger" id="delete">
                     <span class="glyphicon glyphicon-remove"></span> Delete Selected
                  </button></div></li>
                @endif
            @endif

            @if(isset($trashed)&&$trashed=='')
            <li> <div class="dropdown">
              <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <span class="glyphicon glyphicon-envelope"></span>
                Email
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenu1">

               @if(count($emailsets['emailsets'])>0)
                 @if(in_array('notify_donor',$emailsets['active_triggers']))
                    <li><a href="#" id="notify" ><span class="glyphicon glyphicon-envelope"></span> Send Account Setup Notification to <span id="selected"><strong>All {!!$counts['all']!!} Donors</strong></span> @if(count($template_errors[$emailsets['default_emailset']['id']]['notify_donor'])>1){!!reset($template_errors[$emailsets['default_emailset']['id']]['notify_donor'])!!} @endif</a>

                    </li>
                    <li class="divider"></li>
                  @else
                    <li><a href="{!!URL::to('admin/edit_emailtemplate',array($emailsets['default_emailset']['id'],'notify_donor'))!!}" ><span class="glyphicon glyphicon-envelope"></span> You must setup the "Notify Donor of Account Setup" Email Template to send this email.</a></li>
                  @endif

                @if(in_array('year_end_statement',$emailsets['active_triggers']))
                @if(count($years)==0)
                <li> <a href="#"> No Donations found. </a></li>
                @endif
                  @foreach($years as $year)
                        <li><a href="#" id="statement_{!!$year!!}" ><span class="glyphicon glyphicon-envelope"></span> Send {!!$year!!} Year End Statment to <span id="selected"><strong>All {!!$counts['all']!!} Donors</strong></span> @if(count($template_errors[$emailsets['default_emailset']['id']]['year_end_statement'])>1){!!reset($template_errors[$emailsets['default_emailset']['id']]['year_end_statement'])!!} @endif</a> </li>
                  @endforeach
                @else
                    <li class="divider"></li>
                  <li><a href="{!!URL::to('admin/edit_emailtemplate',array($emailsets['default_emailset']['id'],'year_end_statement'))!!}" ><span class="glyphicon glyphicon-envelope"></span> You must setup the "Donor Year End Statement" Email Template to send this email. </a></li>
                @endif


                @if(count($emailsets['emailsets'])>0)
                <li class="divider"></li>
               <li> <a href="{!!URL::to('admin/email')!!}"><span class="glyphicon glyphicon-send"></span> Currenlty using Emailset: <strong> {!!$emailsets['default_emailset']['name']!!}</strong>  @if(count($template_errors[$emailsets['default_emailset']['id']]['notify_donor'])>1||count($template_errors[$emailsets['default_emailset']['id']]['year_end_statement'])>1) <span class="label label-warning">View Warnings</span> @endif </a></li>
                @endif

                @foreach($emailsets['emailsets'] as $k => $set)
                    @if($k!=$emailsets['default_emailset']['id'])
                      <li class="divider"></li>
                      <li><a href="{!!URL::to('admin/change_default_emailset',array($hysform->id,$k))!!}"><span class="glyphicon glyphicon-transfer"></span> Switch to <strong>{!!$set['name']!!}</strong> emailset</a></li>
                    @endif
                @endforeach

                @else
                  <li> <a href="{!!URL::to('admin/email')!!}"><span class="glyphicon glyphicon-send"></span> You must create an "Auto Email Set" to Send Emails</a></li>
                @endif
               
              </ul></div></li>
              @endif

            @if(empty($disabled))
			<li><div class="btn-group">
	            <button type="button" class="btn btn-default" href="#" id="settings">
	               <span class="glyphicon glyphicon-th-list"></span> Edit Reports
	            </button>
	            @if (isset($reports)&&! $reports->isEmpty())
             <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu" role="menu">
            	<li><a> Select a saved report</a></li>
            	<li class="divider"></li>
				@foreach ($reports as $report)
				<li><a href="{!! URL::to('admin/select_donor_saved_report', array($report->id, $hysform_id,$trashed)) !!}">{!! $report->name !!}</a></li>
				@endforeach
            </ul>
			@endif
            </div></li>
            @endif
           
			<li> <div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                 <span class="glyphicon glyphicon-eye-open"></span> View <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu" style="width:250px;">
                @if (isset($trashed)&&$trashed == false&&!isset($sponsorships))
					<li>
						<a href="#"> 
							<span class="glyphicon glyphicon-align-justify"></span>
							<strong> View All Donors</strong> 
							<span class="badge pull-right">{!!$counts['all']!!}</span> 
						</a>
					</li>
				@else
				<li><a href="{!! URL::to('admin/show_all_donors', array($hysform_id, false)) !!}"><span class="glyphicon glyphicon-align-justify"></span> View All Donors <span class="badge pull-right">{!!$counts['all']!!}</span></a>  </li>
			    @endif

           		@if (isset($trashed)&&$trashed == '1')
					<li><a href="#"><span class="glyphicon glyphicon-trash"></span> <strong> View Archived Donors </strong>  <span class="badge pull-right">{!!$counts['trashed']!!}</span> </a></li>
				@else
					<li><a href="{!! URL::to('admin/show_all_donors', array($hysform_id, '1')) !!}"><span class="glyphicon glyphicon-trash"></span> View Archived Donors  <span class="badge pull-right">{!!$counts['trashed']!!}</span> </a></li>
				@endif

            </ul>
            
        </div><!-- /btn-default --></li>

        	

            <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-question-sign"></span> About Donors
            </button></a></div></li>

			<li class="pull-right">
			<div id="collapseOne" class="panel panel-default panel-collapse collapse">
			<div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
               	<div class="panel-actions">
                    	<div class="label label-success">Info</div>
                </div>
                <h3 class="panel-title">About The Donors Table</h3>
            </div><!-- /panel-heading -->
	              <div class="panel-body">
	              Each Row of the table you see below represents one Donor.<br/>
	              You may change which columns display by clicking 'edit reports.'<br/>
	              If you want to modify the actual fields, you must <a href="{!!URL::to('admin/manage_form/'.$hysform_id)!!}">change the donor form itself.</a>
	              </div>
	            </div>
            </li>
            
		</ul>