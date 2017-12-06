@extends('admin.default')

@section('headerscripts')
{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

@stop

@section('content')

	<h1><small><a href="{!!URL::to('admin/show_all_donors',array($hysform->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $hysform->name !!} </a></small>
	<div class="btn-group"><a href="{!! URL::to('admin/add_donor', array($hysform->id)) !!}">

            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-plus"></span> Add Donor
            </button></a></div>
	</h1>
    

    <h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $dname !!} <small><span class="glyphicon glyphicon-link"></span> <em>Sponsorships</em>@if(isset($donor)&&$donor['deleted_at']!=null) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small></h1>
    
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

    @include('admin.views.donorMenu')

    <div class="magic-layout">
			<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
	               	<div class="panel-actions">
	                    	<div class="label label-success">New Sponsorship</div>
	                </div>
	                <h3 class="panel-title">Confirm Sponsorship of {!!$name['name']!!}</h3>
	            </div><!-- /panel-heading -->
	            <div class="panel-body">
		<div class="col-md-8">
			{!! Form::open(array('url' => 'admin/add_sponsorships/'.$id.'')) !!}
				<div class="form-group">
					{!! Form::label('name', 'Name') !!}
					{!! Form::text('name', $name['name'], $attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled')) !!}
				</div>
					{!! Form::hidden('entity_id', $name['id']) !!}
				
				@if ($vars['program_type'] == 'contribution')
					@if(empty($vars['sp_amount'][0]))
						<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							{!! Form::text('sp_amount','',array('class' => 'form-control')) !!}
						</div>
					@else
						<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							<select class="form-control" name="sp_amount">
								@foreach ($vars['sp_amount'] as $amount) 
									<option value="{!! $amount !!}">{!! $vars['symbol'] !!}{{ $amount }}</option>
								@endforeach
							</select>
						</div>
					@endif
				@endif
				
				@if ($vars['program_type'] == 'number')
					@if(empty($vars['sp_amount']))
						<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							{!! Form::text('sp_amount','',array('class' => 'form-control')) !!}
						</div>
					@else
						<div class="form-group">
							{!! Form::label('amount', 'Sponsorship Amount') !!}
							{!! Form::text('amount', $vars['symbol'].$vars['sp_amount'], $attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled')) !!}
						</div>
					@endif
					{!! Form::hidden('sp_amount', $vars['sp_amount']) !!}
				@endif



				@if ($vars['program_type'] == 'funding')
					@if(empty($vars['sp_amount'][0]))
						<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							{!! Form::text('sp_amount','',array('class' => 'form-control')) !!}
						</div>
					@else
						<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							<select class="form-control" name="sp_amount">
								@foreach ($vars['sp_amount'] as $amount) 
									<option value="{!! $amount !!}">{!! $vars['symbol'] !!}{{ $amount }}</option>
								@endforeach
							</select>
						</div>
					@endif
				@endif
				
				<div class="form-group">
					{!! Form::label('method', 'Payment Method') !!}
					{!! Form::select('method', $dntns->getMethods(), null, array('class' => 'form-control')) !!}
				</div>

				<div id="arb_subscription_id" class="form-group">
				</div>

				<div class="form-group">
					{!! Form::label('frequency', 'Payment Frequency') !!}
					{!! Form::select('frequency', $dntns->getFrequencies(), null, array('class' => 'form-control')) !!}
				</div>

				<div class="form-group">
					{!! Form::label('frequency', 'Next Payment Due') !!}
					{!! Form::text('next', null, array('placeholder'=> 'Format Date YYYY-MM-DD','class' => 'form-control datepicker')) !!}
				</div>

				@if(count($programs)>1)
					<div class="form-group">
						{!! Form::label('program_id', 'Program Assignment') !!}
						{!! Form::select('program_id', $programs,'', array('class' => 'form-control')) !!}
					</div>
				@endif
				
					@if(!empty($email_template)&&$email_template->disabled==0)
					<div class="form-group">
						<label>
						{!! Form::checkbox('send_email', 1) !!}
						Send New Donor Signup email?
						</label>
					</div>
					@else
					<div class="form-group">
						<label>
						@if(!empty($email_template))
							<div class='alert alert-warning alert-icon'><div class="icon"><i class="icon ion-ios7-information-empty"></i></div>
								Warning: Donor Signup Email will not be sent, as it has been disabled <a href="{!!URL::to('admin/edit_emailtemplate/'.$email_template->id.'/new_donor')!!}">in templates</a>.
							</div>
						@else
							<div class='alert alert-warning alert-icon'><div class="icon"><i class="icon ion-ios7-information-empty"></i></div>
								Warning: Donor Signup Email will not be sent, because this program has <a href="{!!URL::to('admin/program_settings/'.$program->id)!!}">no "Email Templates Set" attached to it</a>.
							</div>
						@endif
						</label>
					</div>
					@endif
				
				@if (isset($vars['end_date'])) 
					<p>Sponsorship will end: {!! Carbon::createFromTimeStamp(strtotime($vars['end_date']))->toFormattedDateString() !!}</p>
					{!! Form::hidden('until', $vars['end_date']) !!}
				@endif
				
				{!! Form::submit('Create Sponsorship', array('class' => 'btn btn-primary')) !!}
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
		$( ".datepicker" ).datepicker({ dateFormat: "yy-mm-dd" });	
		$('.magic-layout').isotope('reLayout');
		$('#method').change(function() {
				var selected = $(this).val();
				
				console.log(selected);
				if ( selected == 5 ) {
					$('div#arb_subscription_id').html('{!! Form::label('arb_subscription_id', 'ARB Subscription ID') !!}
						{!! Form::text('arb_subscription_id', null, $attributes = array('class' => 'form-control')) !!}');
				} else {
					$('div#arb_subscription_id').html('');
				}
				$('.magic-layout').isotope('reLayout');
			});
	});
	</script>
@stop