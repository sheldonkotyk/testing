@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/demo_table.css') !!}
	{!! HTML::style('media/css/TableTools.css') !!}
	{!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('media/js/TableTools.js') !!}
    {!! HTML::script('media/js/ZeroClipboard.js') !!}
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	
	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Message Manager <small> <span class="glyphicon glyphicon-envelope"></span> View All Donor - Recipient Messages</small></h2>
	
	@include('admin.views.donorEmailMenu')


	<div class="app-body">

		<div class="magic-layout">
		                                        
				

			@if (!empty($a_emails))
			<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-envelope"></i></div>
	                <div class="panel-actions">
	                    <div class="badge"></div>
	                </div>
		               
		                <h3 class="panel-title">Messages Assigned To Me</h3>
		            </div><!-- /panel-heading -->
		            <div class="panel-body">
						<table class="table table-striped table-condensed">
							<thead>
								<th>Status</th>
								<th>To</th>
								<th>From</th>
								<th>Subject</th>
								<th>Message</th>
								<th>Date</th>
								<th>Manage</th>
							</thead>
							
							<tbody>
							@foreach ($a_emails as $email)
								<tr>
									@if ($email['status'] == 1)
									<td><span class="label label-success">New</span></td>
									@elseif ($email['status'] == 2)
									<td><span class="label label-warning">In Process</span></td>
									@elseif ($email['status'] == 3)
									<td><span class="label label-default">Complete</span></td>
									@else
									<td></td>
									@endif
									
									<td>{!! $email['to']['name'] !!}</td>
									<td class="text-warning">{!! $email['from']['name'] !!}</td>
									<td class="text-muted">{!! $email['subject'] !!}</td>
									<td class="text-muted"><em>{!! mb_substr($email['message'], 0, 35) !!}</em></td>
									<td>{!! Carbon::createFromTimeStamp(strtotime($email['date']))->toFormattedDateString() !!}</td>
									<td><a class="btn btn-default btn-xs" href="{!! URL::to('admin/view_email', array($email['id'])) !!}">View</a></td>
								</tr>
							@endforeach
							</tbody>
						</table>
						</div>
						</div>
						@endif
				<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-envelope"></i></div>
	                <div class="panel-actions">
	                    <div class="badge"></div>
	                </div>
		               
		                <h3 class="panel-title">All Messages</h3>
		            </div><!-- /panel-heading -->
		            <div class="panel-body">
						<table class="table table-striped table-condensed">
							<thead>
								<th>Status</th>
								<th>To</th>
								<th>From</th>
								<th>Subject</th>
								<th>Message</th>
								<th>Date</th>
								<th>Manage</th>
							</thead>
							
							<tbody>
							@foreach ($emails as $email)
								<tr>
									@if ($email['status'] == 1)
									<td><span class="label label-success">New</span></td>
									@elseif ($email['status'] == 2)
									<td><span class="label label-warning">In Process</span></td>
									@elseif ($email['status'] == 3)
									<td><span class="label label-default">Complete</span></td>
									@else
									<td></td>
									@endif
									
									<td>{!! $email['to']['name'] !!}</td>
									<td class="text-warning">{!! $email['from']['name'] !!}</td>
									<td class="text-muted">{!! $email['subject'] !!}</td>
									<td class="text-muted"><em>{!! mb_substr($email['message'], 0, 35) !!}</em></td>
									<td>{!! Carbon::createFromTimeStamp(strtotime($email['date']))->toFormattedDateString() !!}</td>
									<td><a class="btn btn-default btn-xs" href="{!! URL::to('admin/view_email', array($email['id'])) !!}">View</a></td>
								</tr>
							@endforeach
							</tbody>
						</table>
						</div>
						</div>
						</div>
						</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$('.table').dataTable( {
			"bStateSave" : true,
			"sDom": 'T<"clear">lfrtip',
			"oTableTools" : {
			    "sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
			    "aButtons": [ "copy","csv","pdf","print" ]
			},
			"oLanguage" : {
			"sLengthMenu" : 'Show <select>' +
			'<option value="10">10</option>' +
			'<option value="25">25</option>' +
			'<option value="50">50</option>' +
			'<option value="100">100</option>' +
			'<option value="-1">All</option>' +
			'</select> Entries',
			"sProcessing" : 'Processing...<div class="progress progress-striped active"><div class="bar" style="width:100%;"></div></div>'
			},
			"fnInfoCallback": function () {
            $('.magic-layout').isotope('reLayout'); // relayout .magic-layout
        }
		});
	});
	</script>
@stop