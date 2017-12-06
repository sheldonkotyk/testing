@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
<h2>{!!Client::find(Session::get('client_id'))->organization!!} Settings <small><span class="glyphicon glyphicon-plus"></span> Create Settings</small></h2>
	
@include('admin.views.settingsMenu')
<div class="app-body">
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
		    <div class="panel-heading">
		        <div class="panel-icon"><i class="glyphicon glyphicon-plus"></i></div>
		        <div class="panel-actions">
		                <span class="label label-success">New Settings</span>
		        </div>
		        <h3 class="panel-title">Create Settings</h3>
		    </div><!-- /panel-heading -->
		    <div class="panel-body">


		<div class="row">
				{!! Form::open(array('class'=>'form-horizontal','role'=>'form')) !!}
					<div class="form-group">

					<!-- <label class="col-sm-2 control-label" > <strong>Settings Name</strong> </label> -->
					{!! Form::label('name', 'Name',array('class'=>'col-sm-2 control-label')) !!}
                   		<div class="col-sm-10">
                   			<div class="input-group">
								{!! Form::text('name', $value = null, $attributes = array('placeholder' => 'Give these settings a name', 'class' => 'form-control ','required')) !!}
								 <span class="input-group-btn">
	                                <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseName">
	                                <span class="glyphicon glyphicon-question-sign"></span></button>
	                             </span>
							</div>
							<div id="collapseName" class="collapse">
	                        <br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                       <strong>About Setting Name : </strong><br/> 
				                       Each Settings must have it's own individual name.<br/>
				                       This name should reflect what the settings are used for. <br/>
				                       You may have individual settings for each program, or multiple programs may also share the same settings.<br/>
				                       Consider these things and name your settings accordingly.<br/>
				                       <strong>Note: You may also change the name of your settings at a later point if needed.</strong>

				                </div><!-- /alert-info -->
					        </div>
							{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
						</div>
					</div>


					<div class="form-group">
					<div class="col-sm-2">
							<br/>					
						{!! Form::label('program_type', 'Choose Program Type',array('class'=>'control-label')) !!}
					</div>
						<div class="col-sm-10">
							<div class="radio">
								<label>
									{!! Form::radio('program_type', 'contribution') !!}
									Contribution - This requires monthly contributions to reach a certain threshold to complete sponsorship.
								</label>
							</div>
							<div class="radio">
								<label>
									{!! Form::radio('program_type', 'number') !!}
									Number of Sponsors - This requires a certain number of sponsors to complete sponsorship.
								</label>
							</div>
							<div class="radio">
								<label>
									{!! Form::radio('program_type', 'funding') !!}
									Funding - This requires a certain amount of total donations to complete sponsorship.
								</label>
							</div>

							<!-- <div class="radio">
								<label>
									{!! Form::radio('program_type', 'one_time') !!}
									One-Time - This program type defaults to non-recurring donation.
								</label>
							</div>
							{!! $errors->first('program_type', '<p class="text-danger">:message</p>') !!} -->

						</div>
					</div>
					<div  id="type_options">
					
					</div>
					

					
					<div class="form-group">
						{!! Form::label('currency_symbol', 'Currency Symbol',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10">
							<div class="input-group">
								{!! Form::text('currency_symbol', $value = null, $attributes = array('placeholder'=>'Enter something like: "$" or "£" or "Yen"','class' => 'form-control')) !!}
								<span class="input-group-btn">
	                                <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseCurrency">
	                                <span class="glyphicon glyphicon-question-sign"></span></button>
	                             </span>
							</div>
							 <div id="collapseCurrency" class="collapse">
	                        <br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                       <strong>About Currency Symbol : </strong><br/> 
				                       This is the currency symbol that will be used for these settings. <br/> 
				                       The currency symbol that you select will be used for both Donor and Admin pages.<br/>
				                </div><!-- /alert-info -->
					        </div>
						</div>	
					</div>
					
					<div class="form-group">
						{!! Form::label('duration', 'Duration of Program',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10"><br/>
							<div class="input-group">
								{!! Form::text('duration', $value = null, $attributes = array('placeholder' => 'Enter something like: "365" or "2035-05-38"', 'class' => 'form-control')) !!}
								<span class="input-group-btn">
	                                <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseLength">
	                                <span class="glyphicon glyphicon-question-sign"></span></button>
	                             </span>
	                        </div>

							<div id="collapseLength" class="collapse">
	                        <br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                       <strong>About Sponsorship Length : </strong><br/> 
				                       <ul>
					                       <li>Leave empty if the sponsorship program has no end date.</li>
					                       <li>Or enter a number of days (i.e. for 3 months enter 90)</li>
					                       <li>Or enter a date when the program ends. </li>
				                       </ul>
				                       IMPORTANT! Dates must be entered in the format YYYY-MM-DD.
				                </div><!-- /alert-info -->
					        </div>
						</div>
					</div>
					<hr>
				   	<div class="form-group">
				   		{!! Form::label('donor_options', 'Donor Account Options',array('class'=>'col-sm-2 control-label')) !!}
					   	<div class="col-sm-10"> 
						    <div class="checkbox">
								<label>
									{!! Form::checkbox('allow_email', '1') !!}
									Allow Donors to message Recipients - 
	                                <button class="btn btn-sm btn-primary" pull-right type="button" data-toggle="collapse"  href="#collapseDonorEmail">
	                                	<span class="glyphicon glyphicon-question-sign"></span>
	                                </button>
	                                <div id="collapseDonorEmail" class="collapse">
			                        	<br/>
						              	<div class="alert alert-info alert-icon">
						                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
						                       
						                       <strong>About Donor Messaging : </strong><br/>
						                       If you enable this feature, Donors will be able to correspond with their sponsorship recipients.<br/>
						                       This is not a direct email feature. <br/>
						                       The message is sent to the admin in HYS and must be conveyed from there to the Recipient.<br/>
						                       This messaging system always keeps the administrator between the donor and the receipient.<br/><br/>
						                       This is what the Donor account page looks like when this feature is enabled:<br/>
						                       <div class="">
							                       <figure>
								                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+2.55.00+PM.png">
								                       <figcaption><em></em></figcaption>
							                       </figure>
						                       </div>
						                </div><!-- /alert-info -->
						        	</div>
								</label>
							</div>
					
						    <div class="checkbox">
								<label>
									{!! Form::checkbox('show_payment', '1') !!}
									Display Donation history in Donor Account - 
									 <button class="btn btn-sm btn-primary" pull-right type="button" data-toggle="collapse"  href="#collapseDonationHistory">
	                                	<span class="glyphicon glyphicon-question-sign"></span>
	                                </button>
	                                <div id="collapseDonationHistory" class="collapse">
			                        	<br/>
						              	<div class="alert alert-info alert-icon">
						                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
						                       
						                       <strong>About Donation History : </strong><br/>
						                       If you enable this feature, Donors will be able to view their past donations upon login.<br/><br>

						                       Here is what the Donor will see on their account page:<br/>
						                       <div class="">
							                       <figure>
								                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+3.07.18+PM.png">
								                       <figcaption><em></em></figcaption>
							                       </figure>
						                       </div>
						                </div><!-- /alert-info -->
						        	</div>
								</label>
							</div>
						</div>
					</div>

					<hr>
					<div class="form-group">
			   			{!! Form::label('order_page_options', 'Order Page Options',array('class'=>'col-sm-2 control-label')) !!}
					   	<div class="col-sm-10"> 
							<div class="checkbox">
								<label>
									{!! Form::checkbox('designations', '1') !!}
									Display Additional Gifts on order page
									<button class="btn btn-sm btn-primary" pull-right type="button" data-toggle="collapse"  href="#collapseDesignations">
	                                	<span class="glyphicon glyphicon-question-sign"></span>
	                                </button>
	                                <div id="collapseDesignations" class="collapse">
			                        	<br/>
						              	<div class="alert alert-info alert-icon">
						                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
						                       
						                       <strong>About Additional Gifts : </strong><br/>
						                       If you enable this feature, a "Additional Gifts" box will be added to the order page.<br/>
						                       Additional Gifts are for donors to add a desired amount to their donation.<br/>
						                       For this to work, you must configure some Additional Gifts.<br/>
						                       Some example Additional Gifts are: "Classroom Materials" or "New School Building."<br/>
						                       <a href="{!!URL::to('admin/all_designations')!!}">Click here to setup Additional Gifts</a>.
						                </div><!-- /alert-info -->
						        	</div>
								</label>
							</div>


							<div class="checkbox">
								<label>
									{!! Form::checkbox('login_box', '1') !!}
									Allow users to login on order page.

									<button class="btn btn-sm btn-primary" pull-right type="button" data-toggle="collapse"  href="#collapseLoginBox">
	                                	<span class="glyphicon glyphicon-question-sign"></span>
	                                </button>
	                                <div id="collapseLoginBox" class="collapse">
			                        	<br/>
						              	<div class="alert alert-info alert-icon">
						                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
						                       
						                       <strong>About Login on Order Page : </strong><br/>
						                       If you enable this feature, already-signed-up donors can simply type in their username and password to complete an order.<br/>
						                </div><!-- /alert-info -->
						        	</div>
								</label>
							</div>
							
							<div class="checkbox">
								<label>
									{!! Form::checkbox('stripe', '1') !!}
									Use Credit Card processing @if($donation->checkUseCC()!=false) (<strong>{!!ucfirst($donation->checkUseCC())!!}</strong> setup in the <a href="{!!URL::to('admin/edit_client_account')!!}">Account settings</a>) @else ( must be setup in the <a href="{!!URL::to('admin/edit_client_account')!!}">Account settings</a>)@endif 
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('checks', '1') !!}
									Allow users to pay via Check
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('cash', '1') !!}
									Allow users to pay with Cash
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('wire_transfer', '1') !!}
									Allow users to pay via Wire Transfer
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('hide_payment_method', '1') !!}
									Hide the payment Method dialog
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('hide_frequency', '1') !!}
									Disable payment schedule options (defaults to Monthly)
								</label>
							</div>
						</div>
					</div>

					<hr>
					<div class="form-group">
			   			{!! Form::label('front_page_options', 'Front Page Options',array('class'=>'col-sm-2 control-label')) !!}
					   	<div class="col-sm-10"> 
							<div class="checkbox">
								<label>
									{!! Form::checkbox('sorting', '1') !!}
									Allow users to sort on front page
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('display_all', '1') !!}
									Display fully sponsored Recipients
								</label>
							</div>

							<div class="checkbox">
								<label>
									{!! Form::checkbox('disable_program_link', '1') !!}
									Enable Single Recipient View
								</label>
							</div>

						</div>
					</div>
					<hr>
					<div class="form-group">
						{!! Form::label('placeholder', 'Image Placeholder',array('class'=>'col-sm-2 control-label')) !!}
							<div class="col-sm-10"> 
								<div class="input-group">
									{!! Form::text('placeholder', $value = null, $attributes = array('placeholder' => 'Enter URL for Placeholder image', 'class' => 'form-control')) !!}
									<span class="input-group-btn">
		                                <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseImagePlaceholder">
		                                <span class="glyphicon glyphicon-question-sign"></span></button>
		                             </span>
	                             </div>
	                             <div id="collapseImagePlaceholder" class="collapse">
		                        	<br/>
					              	<div class="alert alert-info alert-icon">
					                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
					                       <div class="pull-right">
						                       <figure>
							                       <img src="{!!URL::to('/images/placeholder.gif')!!}" width="80px;">
							                       <figcaption><em>Default Image</em></figcaption>
						                       </figure>
					                       </div>
					                       <strong>About Image Placeholder : </strong><br/>
					                       When no profile photo is set for a Recipient, you can set a custom placeholder image.<br/>
					                       Leave empty for default image placeholder.<br/>
					                       If your website is https, be sure to use an https address for your placeholder.<br/>
					                       <strong>Note: Image must be 300px by 300px.</strong><br/>
					                </div><!-- /alert-info -->
					        	</div>
							</div>
						<p class="help-text"></p> 
					</div>

					<hr>

					<div class="form-group">
						{!! Form::label('info', 'Sponsorship Program Info',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10"> 
							<div class="input-group input-group">
								{!! Form::textarea('info', $value = null, $attributes = array('placeholder' => 'Enter information about your program', 'class' => 'form-control hysTextarea')) !!}
								<span class="input-group-addon btn btn-primary" href="#collapseSponsorshipProgramInfo" type="button" data-toggle="collapse">
	                               	 	 <span class="glyphicon glyphicon-question-sign" ></span>
	                             </span>
							</div>

							 <div id="collapseSponsorshipProgramInfo" class="collapse">
	                        	<br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                	   <strong>About Sponsorship Program Info : </strong><br/>
				                       This information will display below the picture on the profile page on the website.<br/>
				                       <strong>Note: You may use Recipient short codes in this field.</strong><br/>
				                       To use short codes, these settings must first be <a href="{!!URL::to('admin/manage_programs')!!}">connected to a program</a>.
				                       <br/>
				                       If you type this in here:<br>
				                       <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+12.20.47+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                      <br/>
				                      This is what will appear on the frontend<br/>
				                        <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+12.10.34+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                       
				                </div><!-- /alert-info -->
				        	</div>
						</div>
					</div>
			
					<div class="form-group">
						{!! Form::label('text_front', 'Front Page Text',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10"> 
							<div class="input-group">
								{!! Form::textarea('text_front', $value = null, $attributes = array('placeholder' => 'Text to display at the top of the front page', 'class' => 'form-control hysTextarea')) !!}
	                             <span class="input-group-addon btn btn-primary" href="#collapseFrontpageText" type="button" data-toggle="collapse">
	                               	 	 <span class="glyphicon glyphicon-question-sign" ></span>
	                             </span>
							</div>
							<div id="collapseFrontpageText" class="collapse">
	                        	<br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                	   <strong>About Front Page Text : </strong><br/>
				                       This information will display at the top of the frontpage.<br/>
				                       <br/>
				                       If you type this in here:<br>
				                       <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+12.20.29+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                      <br/>
				                      This is what will appear on the frontend:<br/>
				                        <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+12.25.11+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                       
				                </div><!-- /alert-info -->
				        	</div>
						</div>
					</div>
					
					<div class="form-group">
						{!! Form::label('text_profile', 'Profile Page Text',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10"> 
							<div class="input-group">
								{!! Form::textarea('text_profile', $value = null, $attributes = array('placeholder' => 'Text to display at the bottom of the profile page', 'class' => 'form-control hysTextarea')) !!}
								<span class="input-group-addon btn btn-primary" href="#collapseProfilepageText" type="button" data-toggle="collapse">
	                               	 	 <span class="glyphicon glyphicon-question-sign" ></span>
	                             </span>
							</div>
							<div id="collapseProfilepageText" class="collapse">
	                        	<br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                	   <strong>About Profile Page Text : </strong><br/>
				                       This information will display at the bottom of the profile page.<br/>
				                       <br/>
				                       If you type this in here:<br>
				                       <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+12.32.14+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                      <br/>
				                      This is what will appear on the frontend:<br/>
				                        <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+12.32.40+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                       
				                </div><!-- /alert-info -->
				        	</div>
						</div>
					</div>
					
					<div class="form-group">
						{!! Form::label('text_checkout', 'Order Page Text',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10"> 
							<div class="input-group">
								{!! Form::textarea('text_checkout', $value = null, $attributes = array('placeholder' => 'Text to display at the top of the order page', 'class' => 'form-control hysTextarea')) !!}
								<span class="input-group-addon btn btn-primary" href="#collapseOrderpageText" type="button" data-toggle="collapse">
	                               	 	 <span class="glyphicon glyphicon-question-sign" ></span>
	                             </span>
							</div>

						<div id="collapseOrderpageText" class="collapse">
	                        	<br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                	   <strong>About Order Page Text : </strong><br/>
				                       This information will display at the top of the order page.<br/>
				                       <br/>
				                       If you type this in here:<br>
				                       <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+1.30.51+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                      <br/>
				                      This is what will appear on the frontend:<br/>
				                        <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+1.31.32+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                       
				                </div><!-- /alert-info -->
				        	</div>
						</div>
					</div>
					
					<div class="form-group">
						{!! Form::label('text_account', 'Donor Account Text',array('class'=>'col-sm-2 control-label')) !!}
						<div class="col-sm-10"> 
							<div class="input-group">
								{!! Form::textarea('text_account', $value = null, $attributes = array('placeholder' => 'Text to display at the top of the donor account page', 'class' => 'form-control hysTextarea')) !!}
								<span class="input-group-addon btn btn-primary" href="#collapseDonorAccountText" type="button" data-toggle="collapse">
	                               	 	 <span class="glyphicon glyphicon-question-sign" ></span>
	                             </span>
							</div>
						<div id="collapseDonorAccountText" class="collapse">
	                        	<br/>
				              	<div class="alert alert-info alert-icon">
				                	 <div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>
				                	   <strong>About Donor Account Text : </strong><br/>
				                       This information will display at the top of the donor/sponsor's account page after they have logged in to the sponsorship program.<br/>
				                       <br/>
				                       If you type this in here:<br>
				                       <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+1.46.05+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                      <br/>
				                      This is what will appear on the donor's account:<br/>
				                        <div class="">
					                       <figure>
						                       <img src="https://s3-us-west-2.amazonaws.com/hys-help-files/Screen+Shot+2014-07-22+at+1.50.42+PM.png">
						                       <figcaption><em></em></figcaption>
					                       </figure>
				                       </div>
				                       
				                </div><!-- /alert-info -->
				        	</div>
						</div>
					</div>
			
					{!! Form::submit('Create', array('class' => 'btn btn-primary')) !!}
					<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
				{!! Form::close() !!}
			</div>
		</div>
	</div>
</div>
@stop

@section('footerscripts')
<script type="text/javascript">
$(document).ready(function() {
	
	$("input[name='program_type']").change(function(){
		var value = $(this).attr('value');
		if(value == 'contribution') {
			$('div#type_options').html(

				'<div class="form-group">'+
					'{!! Form::label("sp_num", "Amount(s) to Complete Sponsorship", array("class"=>"col-sm-2 control-label")) !!}'+
					'<div class="col-sm-10"><br/>'+
						'<div class="input-group">'+
							'{!! Form::text("sp_num", $value = null, $attributes = array("placeholder" => "Enter something like: 100 or 100,200,300 or leave it blank", "class" => "form-control")) !!}'+
							'<span class="input-group-btn"> <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseCompleteAmount"><span class="glyphicon glyphicon-question-sign"></span></button> </span>'+
						'</div> '+

			 			'<div id="collapseCompleteAmount" class="collapse"><br/>'+
			              	'<div class="alert alert-info alert-icon">'+
			                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
			                       '<strong>About Sponsorship Completion Amount(s) : </strong><br/> '+
			                       'This is the amount that all sponsorship contributions must add up to in order to complete sponsorship.<br/>' +
			                       'This field affects what the the admin is allowed to specify for an individual sponsorship to be completed.<br/> '+
			                       'This can be a single fixed value, a dropdown list, or a blank input box.<br/>'+
			                       '<ul>'+
				                   '    <li> For a single fixed value just type in one number.</li>'+
				                       '<li> For a dropdown with multiple amounts separate the amounts with commas.</li> '+
				                       '<li> For a blank input box that the admin types into, leave it blank.</li>'+
				                       '<li> Note: This field does not accept spaces. </li>'+
			                       '</ul>'+
			                      
			                '</div>'+
			            '</div>'+
					'</div>'+
				'</div>'+

				'<div class="form-group">'+
					'{!! Form::label("labels", "Amount Labels",array("class"=>"col-sm-2 control-label")) !!}'+
					'<div class="col-sm-10">'+
						'<div class="input-group">'+
							'{!! Form::text("labels", $value = null, $attributes = array("placeholder" => "Enter something like: Small,Medium,Large or leave it blank","class" => "form-control")) !!}'+
							'<span class="input-group-btn"> <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseAmountLabels"><span class="glyphicon glyphicon-question-sign"></span></button> </span>'+
						'</div>'+
						'<div id="collapseAmountLabels" class="collapse"><br/>'+
			              	'<div class="alert alert-info alert-icon">'+
			                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
			                       '<strong>About Amount Labels : </strong><br/> '+
			                       'Enter labels for the various sponsorship amounts to designate your sponsorship levels.<br/>'+
			                       'Separate labels by comma, no spaces are allowed.<br/>'+
			                       'Number of labels must match the number of amounts you have entered and be in the same order. <br/>'+
			                       'Labels are not required.'+
			                      
			                '</div><!-- /alert-info -->'+
			            '</div>'+
					'</div>'+
				'</div>' +

				'<div class="form-group">'+
						'{!! Form::label("sponsorship_amount", "Sponsorship Amount(s)",array("class"=>"col-sm-2 control-label")) !!}'+
						'<div class="col-sm-10">'+
						'<br/>'+
							'<div class="input-group">'+
								'{!! Form::text("sponsorship_amount", $value = null, $attributes = array("placeholder"=>"Enter like this: 10 or 10,25,50 or just leave it blank","class" => "form-control")) !!}'+
								 '<span class="input-group-btn">'+
	                                '<button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseSpAmount">'+
	                                '<span class="glyphicon glyphicon-question-sign"></span></button>'+
	                             '</span>'+
	                        '</div>'+
	                        '<div id="collapseSpAmount" class="collapse">'+
	                        '<br/>'+
				              	'<div class="alert alert-info alert-icon">'+
				                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
				                       '<strong>About Sponsorship Amount(s) : </strong><br/> '+
				                       'This is what the Donor pays monthly to sponsor a given recipient.<br/> '+
				                       'This can be a single fixed value, a dropdown list, or a blank input box.<br/>'+
				                       '<ul>'+
					                       '<li> For a single fixed value just type in one number.</li>'+
					                       '<li> For a dropdown with multiple amounts separate the amounts with commas.</li> '+
					                       '<li> For a blank input box that the donor types into, leave it blank.</li>'+
					                       '<li> Note: This field does not accept spaces. </li>'+
				                       '</ul>'+
				                      
				                '</div><!-- /alert-info -->'+
					        '</div>'+
							
							'<p class="help-text"></p>'+
						'</div>'+
						
					'</div>'
				);
		}
		
		if (value == 'number') {
			$('div#type_options').html(
				'<div class="form-group">'+
							'{!! Form::label("number_spon", "Number of Sponsors Needed ", array("class"=>"col-sm-2 control-label")) !!}'+
							'<div class="col-sm-10"><br/>'+
								'<div class="input-group">'+
									'{!! Form::text("number_spon", $value = null, $attributes = array("placeholder" => "Enter something like: 5 or 5,10,20 or leave it blank", "class" => "form-control")) !!}'+
									'<span class="input-group-btn"> <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseNumberSponsors"><span class="glyphicon glyphicon-question-sign"></span></button> </span>'+
								'</div> '+

					 			'<div id="collapseNumberSponsors" class="collapse"><br/>'+
					              	'<div class="alert alert-info alert-icon">'+
					                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
					                       '<strong>About Number of Sponsors Needed : </strong><br/> '+
					                       'This is the number of Sponsors that it takes for a recipient to be fully sponsored.<br/>'+
					                       'This field affects what the the admin is allowed to specify for an individual sponsorship to be completed.<br/> '+
					                       'This can be a single fixed value, a dropdown list, or a blank input box.<br/>'+
					                       '<ul>'+
						                       '<li> For a single fixed value just type in one number.</li>'+
						                       '<li> For a dropdown with multiple amounts separate the amounts with commas.</li> '+
						                       '<li> For a blank input box that the admin types into, leave it blank.</li>'+
						                       '<li> Note: This field does not accept spaces. </li>'+
					                       '</ul>'+
					                      
					                '</div>'+
					            '</div>'+
							'</div>'+
						'</div>'+

						'<div class="form-group">'+
						'{!! Form::label("sponsorship_amount", "Sponsorship Amount(s)",array("class"=>"col-sm-2 control-label")) !!}'+
						'<div class="col-sm-10">'+
						'<br/>'+
							'<div class="input-group">'+
								'{!! Form::text("sponsorship_amount", $value = null, $attributes = array("placeholder"=>"Enter like this: 10 or 10,25,50 or just leave it blank","class" => "form-control")) !!}'+
								 '<span class="input-group-btn">'+
	                                '<button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseSpAmount">'+
	                                '<span class="glyphicon glyphicon-question-sign"></span></button>'+
	                             '</span>'+
	                        '</div>'+
	                        '<div id="collapseSpAmount" class="collapse">'+
	                        '<br/>'+
				              	'<div class="alert alert-info alert-icon">'+
				                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
				                       '<strong>About Sponsorship Amount(s) : </strong><br/> '+
				                       'This is what the Donor pays monthly to sponsor a given recipient.<br/> '+
				                       'This can be a single fixed value, a dropdown list, or a blank input box.<br/>'+
				                       '<ul>'+
					                       '<li> For a single fixed value just type in one number.</li>'+
					                       '<li> For a dropdown with multiple amounts separate the amounts with commas.</li> '+
					                       '<li> For a blank input box that the donor types into, leave it blank.</li>'+
					                       '<li> Note: This field does not accept spaces. </li>'+
				                       '</ul>'+
				                      
				                '</div><!-- /alert-info -->'+
					        '</div>'+
							
							'<p class="help-text"></p>'+
						'</div>'+
						
					'</div>');
			$('div#type_name').html(
				'');
		}

		if (value == 'funding') {
			$('div#type_options').html(
				'<div class="form-group">'+
							'{!! Form::label("number_spon", "Funding Level Needed ", array("class"=>"col-sm-2 control-label")) !!}'+
							'<div class="col-sm-10"><br/>'+
								'<div class="input-group">'+
									'{!! Form::text("number_spon", $value = null, $attributes = array("placeholder" => "Enter something like: 100 or 100,200,500 or leave it blank", "class" => "form-control")) !!}'+
									'<span class="input-group-btn"> <button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseNumberSponsors"><span class="glyphicon glyphicon-question-sign"></span></button> </span>'+
								'</div> '+
								'{!! $errors->first("number_spon", "<p class=\"text-danger\">:message</p>") !!}'+



					 			'<div id="collapseNumberSponsors" class="collapse"><br/>'+
					              	'<div class="alert alert-info alert-icon">'+
					                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
					                      '<strong>About Funding Level Needed : </strong><br/> '+
					                       'This is the amount in total contributions that it takes for a recipient to be fully sponsored.<br/>'+
					                       'This field affects what the the admin is allowed to specify for an individual sponsorship to be completed.<br/> '+
					                       'This can be a single fixed value, a dropdown list, or a blank input box.<br/>'+
					                       '<ul>'+
						                       '<li> For a single fixed value just type in one number.</li>'+
						                       '<li> For a dropdown with multiple amounts separate the amounts with commas.</li> '+
						                       '<li> For a blank input box that the admin types into, leave it blank.</li>'+
						                       '<li> Note: This field does not accept spaces. </li>'+
					                       '</ul>'+
					                      
					                '</div>'+
					            '</div>'+
							'</div>'+
						'</div>'+
						'<div class="form-group">'+
						'{!! Form::label("sponsorship_amount", "Sponsorship Amount(s)",array("class"=>"col-sm-2 control-label")) !!}'+
						'<div class="col-sm-10">'+
						'<br/>'+
							'<div class="input-group">'+
								'{!! Form::text("sponsorship_amount", $value = null, $attributes = array("placeholder"=>"Enter like this: 10 or 10,25,50 or just leave it blank","class" => "form-control")) !!}'+
								 '<span class="input-group-btn">'+
	                                '<button class="btn btn-primary" type="button" data-toggle="collapse"  href="#collapseSpAmount">'+
	                                '<span class="glyphicon glyphicon-question-sign"></span></button>'+
	                             '</span>'+
	                        '</div>'+
	                        '<div id="collapseSpAmount" class="collapse">'+
	                        '<br/>'+
				              	'<div class="alert alert-info alert-icon">'+
				                	 '<div class="icon"><i class="glyphicon glyphicon-question-sign"></i></div>'+
				                       '<strong>About Sponsorship Amount(s) : </strong><br/> '+
				                       'This is what the Donor pays monthly to sponsor a given recipient.<br/> '+
				                       'This can be a single fixed value, a dropdown list, or a blank input box.<br/>'+
				                       '<ul>'+
					                       '<li> For a single fixed value just type in one number.</li>'+
					                       '<li> For a dropdown with multiple amounts separate the amounts with commas.</li> '+
					                       '<li> For a blank input box that the donor types into, leave it blank.</li>'+
					                       '<li> Note: This field does not accept spaces. </li>'+
				                       '</ul>'+
				                      
				                '</div><!-- /alert-info -->'+
					        '</div>'+
							
							'<p class="help-text"></p>'+
						'</div>'+
						
					'</div>');
		}

		$('.magic-layout').isotope('reLayout');
		
	});
	
	$('.hysTextarea').redactor();
});
</script>
@stop