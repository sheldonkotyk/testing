
<?php
if(!isset($trashed))
	$disabled='disabled="disabled"';
else
	$disabled='';
?>
<p>
<ul class="nav nav-pills">

			@if(isset($trashed))
			<li class=""><div class="btn-group"><a href="{!! URL::to('admin/add_entity', array($program->id)) !!}">
            <button type="button" class="btn btn-primary">
               <span class="glyphicon glyphicon-plus"></span> Add Recipient
            </button></a></div></li>

            @else
            <li class=""><div class="btn-group"><a href="#">
            <button type="button" class="btn btn-primary active">
               <span class="glyphicon glyphicon-plus"></span> Add Recipient
            </button></a></div></li>
            @endif
            @if(!isset($sponsorships)&&$program->link_id==0)
	            @if(isset($trashed)&&(($trashed=='')||($trashed=='available')||($trashed=='sponsored')||($trashed=='unsponsored')))
	            	@if(!isset($permissions->disable_entity_archive)||$permissions->disable_entity_archive!='1')
		                <li class=""><div class="btn-group">
		                <button type="button" class="btn btn-default enableOnSelect" id="archive">
		                   <span class="glyphicon glyphicon-trash"></span> Archive Selected
		                </button></div></li>
	                @endif
	            @endif
	            @if(isset($trashed)&&$trashed=='1')
	             	@if(!isset($permissions->disable_entity_restore)||$permissions->disable_entity_restore!='1')
		                <li class=""><div class="btn-group">
		                <button type="button" class="btn btn-default" id="restore">
		                   <span class="glyphicon glyphicon-repeat"></span> Restore Selected
		                </button></div></li>
		            @endif
		            @if(!isset($permissions->disable_entity_delete)||$permissions->disable_entity_delete!='1')
		                <li class=""><div class="btn-group">
		                <button type="button" class="btn btn-danger" id="delete">
		                   <span class="glyphicon glyphicon-remove"></span> Delete Selected
		                </button></div></li>
		            @endif
	            @endif
            @endif

            @if(empty($disabled))
				<li><div class="btn-group">
		            <button type="button" class="btn btn-default" href="#" id="settings">
		               <span class="glyphicon glyphicon-th-list"></span> Edit Report{!!(isset($reports) ? 's': '')!!}
		            </button>
		            @if (isset($reports)&&!$reports->isEmpty())
				         <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
				            <span class="caret"></span>
				            <span class="sr-only">Toggle Dropdown</span>
				        </button>
				        <ul class="dropdown-menu" role="menu">
				        	<li><a> Select a saved report</a></li>
				        	<li class="divider"></li>
							@foreach ($reports as $report)
							<li><a href="{!! URL::to('admin/select_saved_report', array($report->id, $program->id,$trashed)) !!}">{!! $report->name !!}</a></li>
							@endforeach
				        </ul>
					@endif
		        </div></li>
            @endif
           
			<li> <div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                 <span class="glyphicon glyphicon-eye-open"></span> View <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu" style="width:350px;">
                @if (isset($trashed)&&$trashed == false&&!isset($sponsorships))
					<li>
						<a href="#"> 
							<span class="glyphicon glyphicon-align-justify"></span>
							<strong> View All Recipients</strong> 
							<span class="badge pull-right">{!!$counts['all']!!}</span> 
						</a>
					</li>
				@else
				<li><a href="{!! URL::to('admin/show_all_entities', array($program->id, false)) !!}"><span class="glyphicon glyphicon-align-justify"></span> View All Recipients <span class="badge pull-right">{!!$counts['all']!!}</span></a>  </li>
			    @endif

			     @if (isset($trashed)&&$trashed == 'unsponsored'&&!isset($sponsorships))
			    	<li>
				    	<a href="#">
					    	<span class="glyphicon glyphicon-star-empty"></span> 
					    	<strong>View Un-Sponsored Recipients </strong>
					    	<span class="badge pull-right">{!!$counts['unsponsored']!!}</span>
				    	</a>
			    	</li>
			    @else
			    	<li><a href="{!! URL::to('admin/show_all_entities', array($program->id, 'unsponsored')) !!}"><span class="glyphicon glyphicon-star-empty"></span> View Un-Sponsored Recipients <span class="badge pull-right">{!!$counts['unsponsored']!!}</span></a></li>
			    @endif

			    @if (isset($trashed)&&$trashed == 'available'&&!isset($sponsorships))
			    	<li><a href="#"><span class="glyphicon glyphicon-star"></span> <strong> View Available Recipients </strong><span class="badge pull-right">{!!$counts['available']!!}</span></a></li>
			    @else
			    	<li><a href="{!! URL::to('admin/show_all_entities', array($program->id, 'available')) !!}"><span class="glyphicon glyphicon-star"></span> View Available Recipients <span class="badge pull-right">{!!$counts['available']!!}</span></a></li>
			    @endif

			    @if (isset($trashed)&&$trashed == 'sponsored'&&!isset($sponsorships))
			    	<li><a href="#"><span class="glyphicon glyphicon-ok"></span> <strong>View Fully Sponsored Recipients </strong>  <span class="badge pull-right">{!!$counts['sponsored']!!}</span> </a></li>
			    @else
			    	<li><a href="{!! URL::to('admin/show_all_entities', array($program->id, 'sponsored')) !!}"><span class="glyphicon glyphicon-ok"></span> View Fully Sponsored Recipients  <span class="badge pull-right">{!!$counts['sponsored']!!}</span> </a></li>
			    @endif

           		@if (isset($trashed)&&$trashed == '1'&&!isset($sponsorships))
					<li><a href="#"><span class="glyphicon glyphicon-trash"></span> <strong> View Archived Recipients </strong>  <span class="badge pull-right">{!!$counts['trashed']!!}</span> </a></li>
				@else
					<li><a href="{!! URL::to('admin/show_all_entities', array($program->id, '1')) !!}"><span class="glyphicon glyphicon-trash"></span> View Archived Recipients  <span class="badge pull-right">{!!$counts['trashed']!!}</span> </a></li>
				@endif

				<li class="divider"></li>
				@if(isset($sponsorships))
				
					@if(isset($trashed)&&$trashed == '1')
					<li><a href="{!!URL::to('admin/show_all_sponsorships',array($program->id))!!}"><span class="glyphicon glyphicon-link"></span> View Sponsorship Summary <span class="badge pull-right">{!!$counts['sponsorships']!!}</span> </a></li>
		            <li><a href=""><span class="glyphicon glyphicon-trash"></span><strong> View Archived Sponsorships <span class="badge pull-right"> {!!$counts['archived_sponsorships']!!} </span> </strong></a></li>
		            <li>
		            	
			    	</li>
	            	@else
	            	<li><a href=""><span class="glyphicon glyphicon-link"></span><strong> View Sponsorship Summary <span class="badge pull-right">{!!$counts['sponsorships']!!}</span> </strong> </a></li>
	            	<li><a href="{!!URL::to('admin/show_all_sponsorships',array($program->id,'1'))!!}"><span class="glyphicon glyphicon-trash"></span> View Archived Sponsorships <span class="badge pull-right">{!!$counts['archived_sponsorships']!!}</span> </a></li>
	            	@endif

	            @else

	            	<li><a href="{!!URL::to('admin/show_all_sponsorships',array($program->id))!!}"><span class="glyphicon glyphicon-link"></span> View Sponsorship Summary <span class="badge pull-right">{!!$counts['sponsorships']!!}</span> </a></li>
	            	<li><a href="{!!URL::to('admin/show_all_sponsorships',array($program->id,'1'))!!}"><span class="glyphicon glyphicon-trash"></span> View Archived Sponsorships <span class="badge pull-right">{!!$counts['archived_sponsorships']!!}</span> </a></li>
	            @endif

            </ul>
            
        </div><!-- /btn-default --></li>

        	

            @if(isset($sponsorships))
            <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-question-sign"></span> About Sponsorship Summary
            </button></a></div></li>

			<li class="pull-right">
			<div id="collapseOne" class="panel panel-default panel-collapse collapse">
			<div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
               	<div class="panel-actions">
                    	<div class="label label-success">Info</div>
                </div>
                <h3 class="panel-title">About The Sponorship Summary</h3>
            </div><!-- /panel-heading -->
	              <div class="panel-body">
	              Each Row of the table you see below represents one individual sponsorship relationship.<br/>
	              The Recipient fields are <span class="text-info"> colored in blue</span>, and the Donor fields are <span class="text-success">colored in green</span> for clarity.<br/>
	              You may change the columns which columns display by clicking 'edit reports.'
	              </div>
	            </div>
            </li>
            @else
            <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-question-sign"></span> About Recipients
            </button></a></div></li>

			<li class="pull-right">
			<div id="collapseOne" class="panel panel-default panel-collapse collapse">
			<div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
               	<div class="panel-actions">
                    	<div class="label label-success">Info</div>
                </div>
                <h3 class="panel-title">About The Recipients Table</h3>
            </div><!-- /panel-heading -->
	              <div class="panel-body">
	              Each Row of the table you see below represents one sponsorship recipient.<br/>
	              You may change which columns appear by clicking 'edit reports.'<br/>
	              You can quickly access saved reports by clicking on the small arrow next to the "Edit Reports" button.<br/>
	              The columns in the table come directly from the form that you setup when you made this program. <br/>
	              If you wish to change the acutal fields, rather than simply which fields appear, <a href="{!!URL::to('admin/manage_form/'.$program->hysform_id)!!}">you must change the form.</a>
	              </div>
	            </div>
            </li>
            @endif
		</ul>
</p>