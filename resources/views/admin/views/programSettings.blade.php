@extends('admin.default')

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<h1><small><a href="{!!URL::to('admin/manage_program')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Programs  </a></small></h1>

	<h2>{!! $program->name !!} <small><span class="icon ion-wrench"></span> Setup Program </small></h2>	

@include('admin.views.programsMenu')


<div class="app-body">
    
    <!-- app content here -->
    <!-- Magic Layout, also try to use  data-cols="3" or "4" -->
    <div class="magic-layout">
        

         <div id="panel-code" class="panel panel-default magic-element magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-ios7-photos-outline"></i></div>
                <h3 class="panel-title">Embed Code</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">
              <h4 >Iframe Embed Code 
	              <div class="btn-group"><a data-toggle="collapse" href="#collapseIframe">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> Iframe Scrolling Problem
	            </button></a></div>
             </h4>
             	<div id="collapseIframe" class="panel panel-info panel-collapse collapse">
					
			              <div class="panel-body">
			              	<h4>Iframe Scrolling Problem</h4>
			              	<strong>The Problem:</strong> When the donor clicks a button, they have to scroll back to the top of the page.<br><br>
			              	<strong>Why does this happen?</strong> Because the HYS Iframe is a window within your website, the web browser doesn't realize that the page needs to be scrolled back to the top. Because of browser security constraints, we can't (from inside the iframe) tell your page to scroll to the top.<br><br>
			              	<strong>The Fix:</strong> However, you can fix this very easily in the code on your website. <br>
			              	There are three ways you can remedy this:<br>
			              	<ul class="list-group">
				              	<li class="list-group-item">
				              		<h4 class="list-group-item-heading">1. Scroll to Top of Page <small>Easy</small></h4>Scrolls to the top of the page every time the iframe changes.<br>
				              		 Add this code to your iframe tag<br>
				              		<pre class="prettyprint">onload="scroll(0,0);"</pre>
				              		<em>This option is best if your iframe code is placed near the top of your page.</em>
				              	</li>
			              		<li class="list-group-item">
				              		<h4 class="list-group-item-heading">2. Scroll to Specified Location <small>Precise</small></h4>Scrolls to the specified location every time the iframe changes. <br>
				              		First Determine the location you want the page to scroll to (we recommend just above the iframe.)<br>
				              		Then insert this code in that location <br>
				              		<pre class="prettyprint">&lt;div id="Iframe"&gt;&lt;/div&gt;</pre>
				              		Then add this code to your iframe tag<br>
				              		<pre class="prettyprint">onload="location.href='#Iframe'"</pre>
				              		<em>This option is best if your iframe code is not near the top of your page. </em>
				              	</li>
				              	<li class="list-group-item">
				              		<h4 class="list-group-item-heading">3. Don't use Iframe <small>Coders Only</small></h4><br>
				              		If you know what you are doing, you can directly link the frontend <a href="{!! URL::to('frontend/view_all', array(Session::get('client_id'), $program->id)) !!}" target="_blank">({!! $program->name !!})</a> to your website and <a href="{!!URL::to('admin/template')!!}">customize it</a> so that the frontend sponsorship page looks just like another page on your website.<br>
				              		<a href="http://help.helpyousponsor.com/read/edit_front_end_template">Click here to find out more.</a>
				              	</li>
			              	</ul>

			              </div>
	            </div>
               <pre class="prettyprint">&lt;iframe class="hysiframe" src="{!! URL::to('frontend/view_all', array(Session::get('client_id'), $program->id)) !!}" style="border:0px #FFFFFF none;" name="HYSiFrame" scrolling="no" frameborder="1" height="1500px" marginheight="0px" marginwidth="0px" width="100%"&gt;&lt;/iframe&gt;</pre>
            	<h4>View Program, Infinite Scrolling (for testing): <a href="{!! URL::to('frontend/view_all', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! $program->name !!}</a></h4>
            	<h4>View Program, Pagination (for testing): <a href="{!! URL::to('frontend/view_pages', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! $program->name !!}</a></h4>

            </div><!-- /panel-body -->
        </div><!-- /panel-code -->


        <div id="panel-alerts" class="panel panel-default magic-element">
            <div class="panel-heading">
            	<div class="panel-actions">
                	
                	<div class="btn-group btn-group-xs">
                    	<button type="button" data-toggle="collapse" class="btn btn-default" href="#collapseSettings">
                    		<span class="glyphicon glyphicon-question-sign"></span>
                    		Program Settings
                    	</button>
                	</div>
                </div>
                <div class="panel-icon"><i class="icon ion-ios7-gear"></i></div>
                <h3 class="panel-title">Attach Program Settings</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

                

                <div id="collapseSettings" class="collapse">
					
			              <div class="alert alert-info alert-icon">
			                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
			                     <strong>About Settings:</strong> Settings control how your program works and what options are available to your Donors. <em><a href="http://help.helpyousponsor.com/read/settings" >Click here to read more about Settings.</a></em>
			                </div><!-- /alert-info -->
			              
			        </div>

				@if($program->link_id==0) <!-- if the program is not a subordniate program -->
					{!! Form::open(array('url' => 'admin/add_settings_to_program/'.$program->id.'', 'class' => 'form-inline')) !!}
					<div class="form-group">
						<select class="form-control" name="settings_id">
							<option value="">Please Select Settings</option>
							@foreach ($settings as $setting) 
								@if($setting->id==$program->setting_id)
								<option value="{!! $setting->id !!}" selected>{!! $setting->name !!}</option>
								@else
								<option value="{!! $setting->id !!}">{!! $setting->name !!}</option>
								@endif
							@endforeach
						</select>
					</div>
						{!! Form::submit('Attach', array('class' => 'btn btn-primary')) !!}
					{!! Form::close() !!}
				@else <!-- if the program is a subordniate program -->
					@if($parent!=null)
						{!! Form::open(array('url' => 'admin/add_settings_to_program/'.$program->id.'', 'class' => 'form-inline')) !!}
						<div class="form-group">
							<select class="form-control" name="settings_id">
								<option value="">Please Select Settings</option>
								@foreach ($settings as $setting) 
									<?php $p_settings= json_decode($setting->program_settings)?>
									@if($parent_settings->program_type==$p_settings->program_type)
										@if($setting->id==$program->setting_id)
										<option value="{!! $setting->id !!}" selected>{!! $setting->name !!}</option>
										@else
										<option value="{!! $setting->id !!}">{!! $setting->name !!}</option>
										@endif
									@endif

								@endforeach
							</select>
						</div>
							{!! Form::submit('Attach', array('class' => 'btn btn-primary')) !!}
						{!! Form::close() !!}
						<h5>Only settings that are set to <strong>{!!$parent_settings->program_type!!}</strong> type are allowed to be attached to this subordinate program.</br>
						If you would like to change the type, you must alter it in the parent program, which is <a href="{!!URL::to('admin/program_settings/'.$parent->id)!!}">{!!$parent->name!!}</a></h5>
					@endif
				@endif
				<hr>
				
				@foreach ($settings as $setting)
					@if ($setting->id == $program->setting_id)
					
					<div class="alert alert-success">
                        <span class="glyphicon glyphicon-ok"></span>
                        <strong>Attached! </strong>Currently Using Settings: <a href="{!! URL::to('admin/edit_settings', array($setting->id)) !!}">{!! $setting->name !!}</a>
                    </div><!-- /alert-success -->
				@endif
				@endforeach

				@if($program->setting_id==0)
					<div class="alert alert-danger ">
	                    <span class="glyphicon glyphicon-warning-sign"></span>
	                    <strong>Error :</strong> No settings are currently attached to this program.
	                </div><!-- /alert-warning -->
				@endif
				
            </div><!-- /panel-body -->
        </div><!-- /panel-alerts -->

       
       <div id="panel-alerts" class="panel panel-default magic-element">
            <div class="panel-heading">
             	<div class="panel-actions">
                	
                	<div class="btn-group btn-group-xs">
                    	<button type="button" data-toggle="collapse" class="btn btn-default" href="#collapseRecipients">
                    		<span class="glyphicon glyphicon-question-sign"></span>
                    		Recipient Form
                    	</button>
                	</div>
                </div>
                <div class="panel-icon"><i class="glyphicon glyphicon-list-alt"></i></div>
                <h3 class="panel-title">Attach Recipient Form</h3>

               
            </div><!-- /panel-heading -->
            <div class="panel-body">

                
              
					<div id="collapseRecipients" class="collapse">
					
			              <div class="alert alert-info alert-icon">
			                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
			                    <strong>About Recipient Form:</strong> The Recipient Form contains the structure used for storing information about the sponsorship recipient. 
			                </div><!-- /alert-info -->
			              
			        </div>

				@if($program->link_id==0) <!-- if the program is not a subordniate program -->
						{!! Form::open(array('url' => 'admin/add_sponsorship_form_to_program/'.$program->id.'', 'class' => 'form-inline')) !!}
						<div class="form-group">
							<select class="form-control" name="sponsorship_form">
								<option value="">Please Select Recipient Form</option>
								@foreach ($hysforms as $form) 
									@if ($form->type == 'entity')
										@if($form->id==$program->hysform_id)
											<option value="{!! $form->id !!}" selected>{!! $form->name !!}</option>
										@else
											<option value="{!! $form->id !!}">{!! $form->name !!}</option>
										@endif
									@endif
								@endforeach
							</select>
						</div>
							{!! Form::submit('Attach', array('class' => 'btn btn-primary')) !!}
						{!! Form::close() !!}
						<hr>
						@foreach ($hysforms as $form)
							@if ($form->id == $program->hysform_id)
						<div class="alert alert-success">
	                        <span class="glyphicon glyphicon-ok"></span>
	                        <strong>Attached! </strong>Current Recipient Form: <a href="{!! URL::to('admin/manage_form', array($form->id)) !!}">{!! $form->name !!}</a>
	                    </div><!-- /alert-success -->
							@endif
						@endforeach
						@if($program->hysform_id==0)

	                        <div class="alert alert-danger ">
		                    	<span class="glyphicon glyphicon-warning-sign"></span>
		                     	<strong>Error :</strong> No sponsorship form is currently attached to this program.
		                	</div><!-- /alert-warning -->
						@endif
				@else <!-- if the program is a subordniate program -->
					@if($parent!=null)

						<?php 
						$temp_p=Program::find($program->link_id);
						?>
						@foreach ($hysforms as $form)
							@if ($form->id == $temp_p->hysform_id)
							<h5><strong>{!! $form->name !!}</strong> will be used by default for this subordinate Program.</br>
							If you would like to change this form, you must alter it in the parent program, which is <a href="{!!URL::to('admin/program_settings/'.$temp_p->id)!!}">{!!$temp_p->name!!}</a></h5>
							@endif
						@endforeach
					@endif
				@endif

             
            </div><!-- /panel-body -->
        </div><!-- /panel-alerts -->


        <div id="panel-alerts" class="panel panel-default magic-element">
            <div class="panel-heading">
            	<div class="panel-actions">
                	
                	<div class="btn-group btn-group-xs">
                    	<button type="button" data-toggle="collapse" class="btn btn-default" href="#collapseDonors">
                    		<span class="glyphicon glyphicon-question-sign"></span>
                    		Donor Form
                    	</button>
                	</div>
                </div>
                <div class="panel-icon"><i class="glyphicon glyphicon-list-alt"></i></div>
                <h3 class="panel-title">Attach Donor Form</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

            	<div id="collapseDonors" class="collapse">
	              	<div class="alert alert-info alert-icon">
	                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	                    <strong>About Donor Form:</strong> The Donor Form contains the structure used for storing your Donor's information.  
	                </div><!-- /alert-info -->
		        </div>
              
				{!! Form::open(array('url' => 'admin/add_donor_form_to_program/'.$program->id.'', 'class' => 'form-inline')) !!}
				<div class="form-group">
					<select class="form-control" name="donor_form">
						<option value="">Please Select Donor Form</option>
						@foreach ($hysforms as $form) 
							@if ($form->type == 'donor')
								@if ($form->id == $program->donor_hysform_id)
								<option value="{!! $form->id !!}" selected>{!! $form->name !!}</option>
								@else
								<option value="{!! $form->id !!}">{!! $form->name !!}</option>
								@endif
							@endif
						@endforeach
					</select>
				</div>
					{!! Form::submit('Attach', array('class' => 'btn btn-primary')) !!}
				{!! Form::close() !!}
				
				<hr>
				@foreach ($hysforms as $form)
					@if ($form->id == $program->donor_hysform_id)
                    <div class="alert alert-success">
                        <span class="glyphicon glyphicon-ok"></span>
                        <strong>Attached! </strong>Current Donor Form: <a href="{!! URL::to('admin/manage_form', array($form->id)) !!}">{!! $form->name !!}</a>
                    </div><!-- /alert-success -->
					@endif
				@endforeach
				@if($program->donor_hysform_id==0)

						<div class="alert alert-danger">
                            <span class="glyphicon glyphicon-warning-sign"></span>
                            <strong>Error :</strong> No Donor form is currently attached to this program.
                        </div><!-- /alert-warning -->
					@endif
				

             
            </div><!-- /panel-body -->
        </div><!-- /panel-alerts -->

    
       <div id="panel-alerts" class="panel panel-default magic-element">
            <div class="panel-heading">
            	<div class="panel-actions">
                	
                	<div class="btn-group btn-group-xs">
                    	<button type="button" data-toggle="collapse" class="btn btn-default" href="#collapseEmails">
                    		<span class="glyphicon glyphicon-question-sign"></span>
                    		Auto Emails
                    	</button>
                	</div>
                </div>
                <div class="panel-icon"><i class="glyphicon glyphicon-edit"></i></div>
                <h3 class="panel-title">Attach Auto Emails</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

            	<div id="collapseEmails" class="collapse">
	              	<div class="alert alert-info alert-icon">
	                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	                     <strong>About Auto Emails:</strong> Auto Emails are used to automatically send emails to Donors and Admins regarding sponsorships. 
	                </div><!-- /alert-info -->
		        </div>

			{!! Form::open(array('url' => 'admin/add_emailsets_to_program/'.$program->id.'', 'class' => 'form-inline')) !!}
			<div class="form-group">
				<select class="form-control" name="emailset_id">
					@foreach ($emailsets as $emailset) 
						<option value="{!! $emailset->id !!}">{!! $emailset->name !!}</option>
					@endforeach
				</select>
			</div>
				{!! Form::submit('Attach', array('class' => 'btn btn-primary')) !!}
			{!! Form::close() !!}
			
			<hr>
			@foreach ($emailsets as $emailset)
				@if ($emailset->id == $program->emailset_id)
	                 <div class="alert alert-success">
	                    <span class="glyphicon glyphicon-ok"></span>
	                    <strong>Attached! </strong>Current Auto Emails Set: <a href="{!! URL::to('admin/email') !!}">{!! $emailset->name !!}</a>
	                </div><!-- /alert-success -->
				@endif
			@endforeach

			@if($program->emailset_id==0)

				<div class="alert alert-danger alert-icon">
	                <span class="glyphicon glyphicon-warning-sign"></span>
	                <strong>Error :</strong> No Auto Emails are currently attached to this program.
	            </div><!-- /alert-warning -->
			@endif
	             
            </div><!-- /panel-body -->
        </div><!-- /panel-alerts -->

        <div id="panel-alerts" class="panel panel-default magic-element">
            <div class="panel-heading">
            	<div class="panel-actions">
                	
                	<div class="btn-group btn-group-xs">
                    	<button type="button" data-toggle="collapse" class="btn btn-default" href="#collapseSubmit">
                    		<span class="glyphicon glyphicon-question-sign"></span>
                    		Progress Report Forms
                    	</button>
                	</div>
                </div>
                <div class="panel-icon"><i class="icon ion-social-buffer"></i></div>
                <h3 class="panel-title">Attach Progress Report Forms</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">


            	<div id="collapseSubmit" class="collapse">
	              	<div class="alert alert-info alert-icon">
	                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	                       <strong>About Progress Report Forms:</strong> Use this form type for creating forms for collecting information about your recipients or donors. When you have information you want to archive this is the form you will use. For example, this form could be used as a contact record for donors or progress reports for children in a child sponsorship program.
	                </div><!-- /alert-info -->
		        </div>
               
			@if($program->link_id==0) <!-- if the program is not a subordniate program -->
				{!! Form::open(array('url' => 'admin/add_submit_form_to_program/'.$program->id.'', 'class' => 'form-inline')) !!}
				<div class="form-group">
					<select class="form-control" name="submit_form">
						@foreach ($hysforms as $form) 
							@if ($form->type == 'submit')
							<option value="{!! $form->id !!}">{!! $form->name !!}</option>
							<?php $progress_exists=true; ?>
							@endif
						@endforeach
					</select>
				</div>
				<div class="form-group">
					<select class="form-control" name="submit_form_type">
						<option value="entity">For Recipients</option>
						<option value="donor">For Donors</option>
					</select>
				</div>
				<?php
				$report_disabled='';
				if(!isset($progress_exists))
					$report_disabled='disabled';
				?>
					{!! Form::submit('Attach', array('class' => 'btn btn-primary',$report_disabled)) !!}
				{!! Form::close() !!}
				
				<hr>
				@if(!isset($progress_exists))
				<h4>No Progress reports have been created. <a href="{!!URL::to('admin/create_form/submit')!!}">Create one here</a>.</h4>
				@endif
				<h6>Current Progress Report Forms:</h6>
				@if(!empty($entity_submit[0]))
					<p>Recipients</p>
					<ul>
					@foreach ($entity_submit as $es)
						@foreach ($hysforms as $form)
							@if ($form->id == $es)
							<li><a href="{!! URL::to('admin/manage_form', array($form->id)) !!}">{!! $form->name !!}</a> | <a href="{!! URL::to('admin/submit_form_notification', array($program->id, $form->id)) !!}"><span class="glyphicon glyphicon-bullhorn"></span></a> | <a href="{!! URL::to('admin/remove_submit_form', array('entity', $program->id, $form->id)) !!}"><span class="glyphicon glyphicon-remove"></span></a></li>
							@endif
						@endforeach
					@endforeach
					</ul>
				@endif
				@if(!empty($donor_submit[0]))
					<p>Donors</p>
					<ul>
					@foreach ($donor_submit as $ds)
						@foreach ($hysforms as $form)
							@if ($form->id == $ds)
							<li><a href="{!! URL::to('admin/manage_form', array($form->id)) !!}">{!! $form->name !!}</a> | <a href="{!! URL::to('admin/remove_submit_form', array('donor', $program->id, $form->id)) !!}"><span class="glyphicon glyphicon-remove"></span></a></li>
							@endif
						@endforeach
					@endforeach
					</ul>
				@endif
				<hr>
			@else <!-- if the program is a subordniate program -->

				<h5>Progress Report forms may not be attached to subordinate programs.</h5>

			@endif
	             
            </div><!-- /panel-body -->
        </div><!-- /panel-alerts -->


    

    </div><!-- /magic-layout -->

</div><!-- /app body -->



@stop

@section('footerscripts')
<script>
$(document).ready(function() {

});
</script>
@stop