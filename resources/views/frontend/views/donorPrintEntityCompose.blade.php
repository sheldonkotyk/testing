@extends('frontend.default')

@section('headerscripts')
	{!!HTML::style('css/redactor.css')!!}
	{!!HTML::script('js/redactor.min.js')!!}
    {!! HTML::script('js/jquery.validate.min.js') !!}
@stop

@section('content')

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif


@if ($allow_emails==1)

@if($parent_email!=null)
	<div class="pull-left" style="width: 100%;">
	<div class="panel panel-success">
		<div class="panel-heading">
		<span class="pull-right badge" >{!!Carbon::createFromTimeStamp(strtotime($parent_email->created_at))->toFormattedDateString()!!}</span>
		<strong>From </strong>{!!$entity_name!!}<br>
		<strong>Subject </strong> {!!$parent_email->subject!!}
		</div>
		<div class="panel-body">
			{!!$parent_email->message!!}
		</div>
	</div>
	</div>
@endif

	{!!Form::open()!!}
	<div class="pull-left" style="width: 100%;">
	<div class="panel panel-default">
		<div class="panel-heading">
			@if($parent_email!=null)
				<span class="pull-right badge" >Reply to <strong>{!!$entity_name!!}</strong></span>
				<strong>From </strong>Me<br>
				<strong>Subject </strong> Re: {!!$parent_email->subject!!}
			@else
				<span class="pull-right badge" >New Message to <strong>{!!$entity_name!!}</strong></span>
				{!! Form::label('subject', 'Subject') !!}
				{!! Form::text('subject', $value = null, $attributes = array('placeholder' => 'Enter Subject', 'class' => 'form-control', 'value required')) !!}
			@endif

			</div>
			<div class="panel-body">

					<div class="form-group">
					@if($parent_email!=null)
					{!! Form::label('message', 'Reply to '.$entity_name) !!}
						{!! Form::textarea('message', $value = null, $attributes = array('placeholder' => 'Reply you wish to send to '.$entity_name, 'class' => 'form-control hysTextarea','maxlength'=> '1500','value required')) !!}
					@else

						{!! Form::label('message', 'Message to '.$entity_name) !!}
						{!! Form::textarea('message', $value = null, $attributes = array('placeholder' => 'Message you wish to send to '.$entity_name, 'class' => 'form-control hysTextarea','maxlength'=> '1500','value required')) !!}
					@endif
					</div>
			 {!! Form::submit('Send', array('class' => 'btn btn-primary form-control')) !!}
			{!!Form::close()!!}
		</div>
		</div>
	</div>

@endif

@stop
@section('footerscripts')
<script>
$(document).ready(function() {
        $('.hysTextarea').redactor();
        $("form").validate();
    
});
</script>
@stop