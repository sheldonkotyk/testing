@extends('frontend.default')

@section('headerscripts')
	{!! HTML::style('css/demo_table.css') !!}
	{!! HTML::style('media/css/TableTools.css') !!}
	{!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('media/js/TableTools.js') !!}
    {!! HTML::script('media/js/ZeroClipboard.js') !!}

<style>
.container {
    overflow-y: scroll;
}
</style>

@stop


@section('content')

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

@if ($allow_emails == 1)


	
			<h4 >Correspondence with <strong>{!!$entity_name!!}</strong></h4>
	
		
			    
			@foreach ($emails as $email)
						<div class="col-md-8 col-sm-8 col-xs-8 col-lg-8 pull-left">
						@if($email->from=='entity')
							<div class='panel panel-success' >
						@else
							<div class='panel panel-default' >
						@endif
							<div class='panel-heading'>
								<span class="pull-right badge" >{!!Carbon::createFromTimeStamp(strtotime($email->created_at))->toFormattedDateString()!!}</span>
								@if($email->from=='entity')
									<strong>From: </strong> {!!$entity_name!!}
								@else
									<strong>From: </strong> Me
								@endif
								<br>
								<strong>Subject: </strong> {!!$email->subject!!}
							</div>
							
							<div class="panel-body">
								{!!$email->message!!}


							</div>

							@if($email->from=='entity')
							<div class="panel-footer">
								<a href="{!!URL::to('frontend/donor_view_entity_compose_message',array($client_id,$program_id,$entity_id,$email->id,$session_id))!!}" class="btn btn-primary">
                                               <span class="glyphicon glyphicon-share-alt"> Reply
                                </a>
								</div>
							@endif
					
						</div>
						</div>

						<?php $child = false ?>
						<?php $first = true ?>
						@foreach ($email_children as $email_kid)
							@if($email_kid->parent_id == $email->id)
								<?php $child = true ?>
								@if($first)
									<div class="col-md-8 pull-right">
									<div class='panel panel-default'>
									<div class="panel-heading"><h3 class="panel-title">Replies to: {!!$email->subject!!}</h3></div>
									<div class="panel-body">
								@endif
								<?php $first = false;?>
								
								@if($email->from=='entity')
									<div class="panel panel-default" >
								@else
									<div class="panel panel-success" style="width:90%;">
								@endif
									<div class="panel-heading">
										<span class="pull-right badge" >{!!Carbon::createFromTimeStamp(strtotime($email_kid->created_at))->toFormattedDateString()!!}</span>
										@if($email->from=='entity')
											<strong>From: </strong> Me
										@else
											<strong>From: </strong> {!!$entity_name!!}
										@endif
										<br>
										<!-- <strong>Subject: </strong>Re: {!!$email->subject!!} -->
									</div>
									
									<div class="panel-body">
										{!!$email_kid->message!!}
									</div>
						
								</div>

							@endif

						@endforeach
						
						@if(!$first)
							</div>
							</div>
							</div>
						@endif
						

			@endforeach



@endif

@stop
@section('footerscripts')
<script>
$(document).ready( function () {

	$('#message_table').dataTable( {
		"bStateSave" : false,
		 "aoColumns": [
    		 { "bSortable": true }],
		"sDom": 'lprti',
		"oLanguage" : {
		"sLengthMenu" : 'Show <select>' +
		'<option value="5">5</option>' +
		'<option value="10">10</option>' +
		'<option value="25">25</option>' +
		'<option value="50">50</option>' +
		'<option value="100">100</option>' +
		'<option value="-1">All</option>' +
		'</select> Entries',
		"sProcessing" : 'Processing...<div class="progress progress-striped active"><div class="bar" style="width:100%;"></div></div>'
		}
	});
  
});
</script>
@stop