@extends('admin.default')

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

	<h1><small><a href="{!!URL::to('admin/forms')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Forms  </a></small></h1>

<h2>{!! $hysform->name !!} ({!!$type_name!!}) <small> <span class="glyphicon glyphicon-pencil"></span> Edit Form Details </small>  {!!(empty($mailchimp_list_name) ? '' : '<small><span class="pull-right "><small> '.$hysform->name.' <span class="glyphicon glyphicon-arrow-right"></span> <img src="'. URL::to('img/Freddie_wink_1.png') .'" style="width:25px;" > '.$mailchimp_list_name.' </small></span></small>') !!}</h2>
	@include('admin.views.fieldsMenu')
	<div class="app-body">
	<div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
                <div class="panel-actions">
                    <div class="label label-success"></div>
                </div>
               
                <h3 class="panel-title">Edit Form Details: {!! $hysform->name !!}</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

				{!! Form::model($hysform) !!}
					<div class="form-group">
						{!! Form::label('name', 'Form Name') !!}
						{!! Form::text('name', $value = null, $attributes = array('placeholder' => 'Give the form a name', 'class' => 'form-control')) !!}
						{!! $errors->first('name', '<p class="text-danger">:message</p>') !!}
					</div>
					
					<div class="form-group">
						{!! Form::label('type', 'Form Type') !!}
						{!! Form::select('type', array('entity' => 'Recipient Profile', 'donor' => 'Donor Profile', 'submit' => 'Submit Only'), null, array('class' => 'form-control','disabled'=>'disabled')) !!}
					</div>

					@if($hysform->type=='donor')

					@if(!empty($lists))
						<div class="form-group">
							{!! Form::label('mailchimp_list_id', 'Sync '.$hysform->name.' to Mailchimp list ') !!}
							{!!Form::select('mailchimp_list_id',$lists,$value = null, array('class' => 'form-control') )!!}
							@if(empty($emailsettings->mailchimp_api))
							<p class='text-help'>To Sync to Mailchimp, you must first <a href="{!!URL::to('admin/edit_client_account#emailsettings')!!}">input your Mailchimp API key on the Account page.</a></p>
							@endif
						</div>
					@endif

					<div class="form-group">
						{!! Form::label('prefix', 'Auto Increment Prefix') !!}
						{!! Form::text('prefix', $value = null, $attributes=  array('placeholder'=> 'Add a prefix to the Auto Increment field for '.$hysform->name ,'class' => 'form-control')) !!}
					</div>
					<div class="form-group">
						{!!Form::checkbox('can_donor_modify_amount','1')!!}
						{!! Form::label('can_donor_modify_amount', 'Allow donors to modify payment amount and schedule.') !!}
					</div>

					<div class="form-group">
						{!!Form::checkbox('hide_payment','1')!!}
						{!! Form::label('hide_payment', 'Remove payment information from donor\'s "My Account" page.') !!}
					</div>

					<div class="form-group">
						{!!Form::checkbox('forgive_missed_payments','1')!!}
						{!! Form::label('forgive_missed_payments', 'Forgive missed payments.' )!!}<br>
						By default, if a Credit Card donor misses payments because their card was refused for any reason, when a new card is added, they will be charged the sponsorship amount daily until their sponsorship is caught up. If you check this box, missed payments will not be charged to donors.
					</div>

					@endif
							
					{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
					<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
				{!! Form::close() !!}
				
			</div>
		</div>
	</div>
	</div>

@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
	});
	</script>
@stop