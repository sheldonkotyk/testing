@extends('admin.default')


@section('content')

@if (Session::get('message'))
	<div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif


  <div class="magic-layout">



    <div class="panel panel-default magic-element">
    <!-- Default panel contents -->
    <div class="panel-body-heading">
     <h2 class="pb-title">
      New HYS Upgrades! <span class="pull-right"><small>Updated: Sept 16th 2015</small></span>
    </h2>
    </div>
    <div class="panel-body">
    
    <strong>Send Year End Statments from HYS!</strong> <br>

    Steps:<br>
    1. Create a <a href="{!!URL::to('admin/email')!!}"> Donor Year End Statement</a> auto email.<br>
    2. Add yourself as a 'Test Donor' with your email address. <br>
    3. Add a test donation for your 'Test Donor' .<br>
    4. From your 'Test Donor' Profile page, use the "Email" Button.<br>
    5. Wait a minute, check your email and view your test year end statement.<br><br>
    <em>If the email looks good, you are ready to send to your Donors!</em><br><br>
    You can send out year end statements three ways: <br>
    <strong>One-at-a-time</strong>: From the Donor Profile page, use the "Email" Button. <br>
    <strong>All</strong>: From the Donors page, use the "Email" Button to send to all donors.<br>
    <strong>Choose</strong>: From the Donors page, click to choose donors, then use the "Email" Button.<br>

    <br><br>

    <strong>Manually Send Notify Donor of Account Setup</strong> <br>
    The "Email" Button found on the "Donors" page and the "Donor Profile" page can also be used to send the "Notify Donor of Account Setup" auto Email.<br>
    If you want to populate your Donors list, and then send the notification emails all at once, now you can!
    <br><br>

    <strong>Mailchimp Auto-Sync Integration</strong><span class="pull-right"><small>Updated: Sept 1st 2015</small></span>
     <a href="https://mailchimp.com" target="_blank"><img class="pull-right" src="{!!URL::to('img/Freddie_wink_1.png')!!}"></a>
    <br><br>
    It's now possible to connect HelpYouSponsor with <a href="https://mailchimp.com" target="_blank">Mailchimp</a>.<br>
    This will automatically sync your donors to a Mailchimp list of your choice.
    <br>

    Step 1: <a href="https://mailchimp.com" target="_blank"> Get yourself a Mailchimp account.</a>
    <br>
    Step 2 : Create a new list in your Mailchimp account.
    <br>
    Step 3: <a href="https://admin.mailchimp.com/account/api-key-popup" target="_blank">Get your Mailchimp API Key.</a>
    <br>
    Step 4: <a href="{!!URL::to('admin/edit_client_account#emailsettings')!!}">go to the Account page</a> and input your Mailchimp API key and click 'Save and Test'
    <br>
    Step 5: <a href="{!!URL::to('admin/forms')!!}">Go to the Forms page</a> and choose your Donor Form, (This will be labeled 'Donor Profile' under the 'Type' column)
    <br>
    Step 6 (optional): On the 'Manage Fields' page you will see the fields for your Donor form. The fields marked as 'title' will be exported to Mailchimp. The first title field will be input as the mailchimp-firstname and any other 'title' fields will be put into the mailchimp-lastname.
    <br>
    Step 7: Click on "Edit Form Details"
    <br>
    Step 8: Select your Mailchimp list and click 'Save'
    <br><br>
    Finished! Now all Donors in that donor-form will be automatically synced to your Mailchimp List.
    </div>
  </div>

    <div class="panel panel-default magic-element">
    <!-- Default panel contents -->
    <div class="panel-body-heading">
     <h2 class="pb-title">
   HYS Upgrades! <span class="pull-right"><small>Updated: July 28th 2015</small></span>
    </h2>
    </div>
    <div class="panel-body">
    <strong>Social Media Links </strong>
      <br>

    
  <ul class="list-group">
  <li class="list-group-item">All Program and Recipient pages now have the following Social Media buttons.</li>
  <li class="list-group-item"><button type="button" class="btn btn-info btn-xs">
                  <img src="{!!URL::to('/images/twitter_white_50.png')!!}" style="width:15px;">Tweet</button> posts the <em>Program</em> or <em>Recipient</em> to your <a href="http://twitter.com">Twitter</a> account.
  </li>
  <li class="list-group-item">
    <button type="button" class="btn btn-info btn-xs">
                  <img src="{!!URL::to('/images/facebook_white_29.png')!!}" style="width:15px;"> Share</button> posts the <em>Program</em> or <em>Recipient</em> to your <a href="http://facebook.com">Facebook</a> page.
  </li>
  <li class="list-group-item">
    <button type="button" class="btn btn-info btn-xs">
      <span class="glyphicon glyphicon-envelope"></span> Email</button> Opens a new email in your email client with a link to the <em>Program</em> or <em>Recipient</em>.
  </li>
  <li class="list-group-item">
  <a data-toggle="collapse" href="#collapseTwo">
              <button type="button" class="btn btn-info btn-xs">
                  <span class="glyphicon glyphicon-link"></span> Embed</button></a> provides the code needed to Embed a <em>Program</em> or <em> Individual Recipient</em> into your website with an iframe. Also, you can access the direct link to a Program or Recipient here.<br>
        </li>
  </ul>
        <div id="collapseTwo" class="panel panel-default panel-collapse collapse">
                  <div class="panel-heading">
                      <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
                      <div class="panel-actions">
                              <div class="label label-success">Info</div>
                      </div>
                      <h3 class="panel-title"> <strong> Billy Jean</strong> Frontend Link</h3>
                  </div><!-- /panel-heading -->
                        <div class="panel-body">
                        <h4 >Iframe Embed Code </h4>
                        <pre class="prettyprint">&lt;iframe class="hysiframe" src="{!!URL::to('frontend/view_entity',array(1,1,1))!!}" style="border:0px #FFFFFF none;" name="HYSiFrame" scrolling="no" frameborder="1" height="1500px" marginheight="0px" marginwidth="0px" width="100%"&gt;&lt;/iframe&gt;</pre>
                        <br>
                        <h4><strong>Billy Jean</strong> Frontend Link: <a href="{!!URL::to('frontend/view_entity',array(1,1,1))!!}" target="_blank">{!!URL::to('frontend/view_entity',array(1,1,1))!!}</a></h4>
                        </div>
                      </div>

      <br>

      <strong>Single Recipient View </strong><br>
    
      <div class="checkbox pull-right" style="border:2px solid #333333; padding; 20px;">
                  <label>
                  {!! Form::checkbox('disable_program_link', '1') !!}
                  Enable Single Recipient View
                </label>
              </div>
                If you go into <a href="{!!URL::to('admin/add_settings')!!}">Program Settings</a>, you will notice a new field. <br>
                This field allows you to isolate any particular recipient for donation in a program.<br>
                So you can have a bunch of recipients in the program, but when this box is checked, the donor will only be able to view and donate to the recipient that was included in the inital link/embed code.<br>
                <br>
                If you find donors overwhelmed by too many choices, or would like to have links to single recipients rather than a whole program, use Single Recipient view.
                <br>
                <em>Note:</em> If you use 'Single Recipient View' it's recommened to embed or share a single recipient rather than a whole program.
                <br><br>
     
      <h3 class="pb-title">
   HYS Upgrade! <span class="pull-right"><small>Updated: July 21st 2015</small></span>
    </h3>
    <br>
    <strong>Personalize HYS emails</strong>
      <br><br>


      In order to have your emails display as being from you, <a href="http://mailgun.com">setup a mailgun account.</a><br>

      Then <a href="{!!URL::to('admin/edit_client_account#emailsettings')!!}"> input your mailgun domain settings here.</a><br>

      Now your emails will make it though safely to your donors without getting trashed.
      <br>
      <br>

      <strong>Mailgun Log</strong><br><br>
      It's now possible to view your mailgun log files right here on HYS.<br>
      Simply <a href="{!!URL::to('admin/edit_client_account#emailsettings')!!}">add your mailgun host and corresponding api key in the account settings.</a> Then click on <a href="{!!URL::to('admin/mailgun_logs')!!}">"View Mailgun Logs"</a> on the account page.
      <br><br>
      Entering the mailgun api key </a> helps us to quickly diagnose any email related problems you may be having.
     


    </div>
  </div>





  



@stop
