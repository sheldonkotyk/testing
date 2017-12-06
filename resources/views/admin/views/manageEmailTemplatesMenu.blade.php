<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url=URL::to('admin/add_emailset');

$view_active='';
$view_url=URL::to('admin/email');

$edit_active='';
if(isset($emailset))
	$edit_url=URL::to('admin/edit_emailset',array($emailset->id));
else
	$edit_url='#';

if($page=='email')
{
	$view_active='active';
	$view_url='#';
}
if($page=='add_emailset')
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
		@if($page=='email'||$page=='add_emailset')
			<li><div class="btn-group"><a href="{!! $add_url !!}"> 
			    <button type="button" class="btn btn-default {!!$add_active!!}">
			       <span class="glyphicon glyphicon-plus"></span> Create Auto Email Set
			    </button></a></div></li>
		@endif

		<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="glyphicon glyphicon-send"></span> View All Auto Emails
		    </button></a></div></li>


	     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> About Auto Emails
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
	                <h3 class="panel-title">About Auto Emails</h3>
	            </div><!-- /panel-heading -->
		              <div class="panel-body">
		              Auto Emails <em>(formerly Email Templates)</em> are used to by HelpYouSponsor to automatically send emails to your donors and admins.<br>
		              <br>
		              When you add short codes <em>(like this [username])</em> to each email, be sure to check on the right hand side of each page, as the allowed short codes differ depending on the template type.<br>


			              <h4>Auto Email Types - When the emails get sent</h4>
						<ul>
							<li><strong>New Donor Signup</strong><ul>
								<li> Sent to Donor when Admin sets up a new sponsorship. </li>
								<li> Sent to Donor when Donor creates sponsorship(s) on the frontend. </li>
							</ul></li>

							<li><strong>Notify Donor of Account Setup</strong><ul>
								<li> Sent to Donor when Admin sets up Donor account. </li>
								<li> Sent to Donor when Admin manually sends (via the "Email" button) from "All Donors" or "Donor Profile" page. </li>
								<li> Sent to Donor when Donor resets their password on the frontend. </li>
							</ul></li>

							<li><strong>Notify Admin of New Sponsorship</strong><ul>
								<li> Sent to Admin when Donor creates sponsorships (one email is sent per sponsorship created.) </li>
							</ul></li>

							<li><strong>Notify Admin of Email from Sponsor</strong><ul>
								<li> Sent to Admin when Donor sends message to Recipient</li>
								<li> Sent to Admin when Donor uploads file to Recipient</li>
							</ul></li>

							<li><strong>Notify Sponsor of Profile Update</strong><ul>
								<li> --In Development-- </li>
							</ul></li>

							<li><strong>Donor Payment Reminder</strong><ul>
								<li> Sends to Donor if Credit card method is set with no Credit Card found. </li>
								<li> Sends to Donor if payment method is set to anything but Credit Card. </li>
							</ul></li>

							<li><strong>Donor Payment Receipt</strong><ul>
								<li>Sent to Donor when Admin records a donation </li>
								<li>Sent to Donor when Donor makes a donation, but only via Credit Card. If donors select Cash, Check or Wire Transfer, the receipt won't be sent and the donation won't be put into the system until the Admin records it. </li>
								<li>Sent to Donor when HelpYouSponsor automatically runs any recurring payment via Credit Card. </li>
							</ul></li>

							<li><strong>Donor Payment Failed</strong><ul>
								<li> Sent to Donor when HelpYouSponsor automatically runs any recurring payment and their Credit Card fails to charge. </li>
							</ul></li>

							<li><strong>Notify Admin of Failed Payment</strong><ul>
								<li> Sent to Admin when HelpYouSponsor automatically runs any recurring payment and their Credit Card fails to charge.  </li>
							</ul></li>

						</ul>
						<br>
						<a href="http://help.helpyousponsor.com/read/setup_email_templates">Click Here to find out more</a>.
		              </div>
		            </div>
	            </li>
	        </ul>
