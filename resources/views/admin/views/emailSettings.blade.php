@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
	
@stop

@section('content')
	
	<h2 id="emailsettings">Email Settings</h2>
	
	<div class="reverse-well">
	
	{!! Form::model($emailsettings, array('url' => 'admin/email_settings')) !!}
		<h2 class="box-heading"> Email Address and Name</h2>
			<p class="help-text">This is the email address and name that will appear to donors when emails are sent by HYS.</p>

			<div class="form-group">
				{!! Form::label('from_address', 'From Email Address') !!}
				{!! Form::text('from_address', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('from_address', '<p class="text-danger">:message</p>') !!}
			</div>

			<div class="form-group">
				{!! Form::label('from_name', 'From Name') !!}
				{!! Form::text('from_name', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('from_name', '<p class="text-danger">:message</p>') !!}
			</div>
			<br>
		<h2 class="box-heading"><img src="{!!URL::to('img/Freddie_wink_1.png')!!}" style="width:40px;"> Mailchimp Integration  </h2>
		<p class="help-text">Connecting to Mailchimp allows you to sync your HYS donors to a <a href="https://mailchimp.com">Mailchimp</a> subscriber list.</p>
		<p class="help-text">Find your Mailchimp API key here. <a href="https://admin.mailchimp.com/account/api-key-popup" target="_blank">https://admin.mailchimp.com/account/api-key-popup</a></p>
			<div class="form-group">
				{!! Form::label('mailchimp_api', 'Mailchimp API key') !!}
				{!! Form::text('mailchimp_api', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('mailchimp_api', '<p class="text-danger">:message</p>') !!}
			</div>
			@if(count($lists))
			<p class="help-text">{!!count($lists)!!} List{!!(count($lists)==1 ? '' : 's' )!!} found. </p>

			<p class="help-text">In order to sync your donors to Mailchimp, go to <a href="{!!URL::to('admin/forms')!!}">Forms</a> -> "Your Donor Form" -> Edit Form Details</p>
			<p class="help-text">Then, select the mailchimp list you wish to populate with information from "Your Donor Form" and hit "Save."</p>
			<p class="help-text">Note 1: You may sync multiple donor forms to a single Mailchimp list, but you cannot sync multiple Mailchimp lists to a single donor form.</p>
			<p class="help-text">Note 2: This is a one-way Sync. This means that the email address for each donor will be sent to your Mailchimp list. Emails added on the Mailchimp website will not be synced to HYS. </p>
			@endif
			<br><br>

		<h2 class="box-heading"><img src="{!!URL::to('img/Mailgun_Icon_small.png')!!}" style="width:40px;"> My Mailgun Settings</h2> 
		<p class="help-text">Set up an account at <a href="http://www.mailgun.com/">mailgun.com</a> and input the fields to use your mailgun account. <br>Leave these fields empty to use the HYS mail server.</p>
			<div class="form-group">
				{!! Form::label('host', 'SMTP Hostname') !!}
				{!! Form::text('host', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('host', '<p class="text-danger">:message</p>') !!}
			</div>

			<div class="form-group">
				{!! Form::label('username', 'SMTP Login') !!}
				{!! Form::text('username', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('username', '<p class="text-danger">:message</p>') !!}
			</div>

			<div class="form-group">
				{!! Form::label('password', 'SMTP Password') !!}
				{!! Form::text('password', $value = null, $attributes = array('class' => 'form-control')) !!}
				{!! $errors->first('password', '<p class="text-danger">:message</p>') !!}
			</div>

			<div class="form-group">
				{!! Form::label('api', 'API Key (Used for viewing the Mailgun Log) ') !!}
				{!! Form::text('api', $value = null, $attributes = array('placeholder' => 'Something like: key-639jlcw42m9nfbnxc1cxzbgqrkua4nc6','class' => 'form-control')) !!}
				{!! $errors->first('api', '<p class="text-danger">:message</p>') !!}
			</div>
			

		{!! Form::submit('Save and Test', array('class' => 'btn btn-primary')) !!}
		@if(!empty($emailsettings->api)&&!empty($emailsettings))
			<p class="pull-right"><a href="{!!URL::to('admin/mailgun_logs')!!}" class="btn btn-default">View Mailgun Logs</a></p>
		@elseif(!empty($emailsettings->host)&&!empty($emailsettings->username)&&!empty($emailsettings->password))
			 <p class="pull-right alert alert-warning"> API Key needed to view Logs </p>
		@endif
	{!! Form::close() !!}

	</div>

@stop