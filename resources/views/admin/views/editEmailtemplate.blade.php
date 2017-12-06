@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

    <h1><small><a href="{!!URL::to('admin/email')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Auto Emails </a></small></h1>

	<h2> {!!$emailset->name!!} <small> <span class="glyphicon glyphicon-envelope"></span> Edit {!!$title!!}</small></h2>

	@include('admin.views.manageEmailTemplatesMenu')

	<div class="app-body">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-envelope"></i>
                </div>
                <div class="panel-actions">
                {!!reset($template_errors[$emailset->id][$trigger])!!}
                </div>
               
                <h3 class="panel-title">Edit {!!$title!!}</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">	

            @if(count($template_errors[$emailset->id][$trigger])>1)
	            <div class="alert alert-warning alert-icon">
	                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
	                <div class="icon"><i class="icon ion-ios7-information-empty"></i></div>
	                <strong>{!!strip_tags(array_shift($template_errors[$emailset->id][$trigger]))!!} </strong> 
	                @foreach($template_errors[$emailset->id][$trigger] as $error)
	               		<br>{!!strip_tags($error)!!}
	                @endforeach
	                <br><strong>Note:</strong> <em>Missing shortcodes will not keep your Auto Emails from sending.</em>
	            </div>
            @endif
				<div class="row">
					<div class="col-md-8 reverse-well">
						{!! Form::model($emailtemplate) !!}
							@if ($to == true)
							<div class="form-group">
								{!! Form::label('to', 'To Email Address') !!}
								{!! Form::text('to', $value = null, $attributes = array('placeholder' => 'Enter Email Address', 'class' => 'form-control')) !!}
								<p class="help-text">Enter multiple email address separated by a comma.</p>
							</div>
							@endif
							
							<div class="form-group">
								{!! Form::label('subject', 'Subject') !!}
								{!! Form::text('subject', $value = null, $attributes = array('placeholder' => 'Enter Email Subject', 'class' => 'form-control')) !!}
							</div>
							
							<div class="form-group">
								{!! Form::label('message', 'Message') !!}
								{!! Form::textarea('message', $value = null, $attributes = array('placeholder' => 'Enter Message', 'class' => 'form-control hysTextarea')) !!}
							</div>
							
							<div class="form-group">
								{!! Form::label('disabled', 'Disable This Email') !!}
								{!! Form::checkbox('disabled', $value = null) !!}
							</div>

							@if (isset($emailtemplate->id)) 
								{!! Form::hidden('id', $emailtemplate->id) !!}
							@endif
							
							{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
							<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
						{!! Form::close() !!}
					</div>
					<div class="col-md-4">
						<div class="reverse-well">
						<h4>Recipient Short Codes</h4>
							@if (!empty($hysform))
							<ul class="list-unstyled">
							@foreach ($hysform as $sc)
								<li><span class="text text-info">[{!! $sc->field_key !!}]</span></li>
							@endforeach
							</ul>
							@elseif($trigger=='pay_receipt') 
								<p>This email template does not have access to Entity Short Codes, you must use the [designations] short code to display entity information.</p>
							
							@elseif($trigger=='pay_remind'||$trigger=='pay_fail'||$trigger=='pay_fail_admin') 
								<p>This email template does not have access to Entity Short Codes, you must use the [designation_name] short code to display entity information.</p>
							@elseif($trigger=='notify_donor') 
							<p>This email template does not have access to Entity Short Codes.</p>

							@else
							<p>This email template set must be attached to a program and the program must have a sponsorship profile form attached to see available short codes.</p>
							@endif

						</div>
						
						<div class="reverse-well">
						<h4>Donor Short Codes</h4>
							<ul class="list-unstyled">
							@if (!empty($donor_hysform))
								@foreach ($donor_hysform as $dsc)
									<li><span class="text text-success">[{!! $dsc->field_key !!}]</span></li>
								@endforeach
							@else 
							</ul>
							
							<p>This email template set must be attached to a program and the program must have a donor profile form attached to see available short codes.</p>
							@endif
						</div>
						
						@if (!empty($shortcodes))
						<div class="reverse-well">
						<h4>Additional Short Codes</h4>
							<ul class="list-unstyled">
	
							@foreach ($shortcodes as $sc)
								<li>[{!! $sc !!}]</li>
							@endforeach
							</ul>
						</div>
						@endif
						
					</div>
				</div>
			</div>
		</div>
	</div> <!-- end app-body -->
@stop

@section('footerscripts')
<script type="text/javascript">
$(document).ready(function() {
	
	$('.hysTextarea').redactor();
});
</script>
@stop