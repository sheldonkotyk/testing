@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/demo_table.css') !!}
	{!! HTML::style('media/css/TableTools.css') !!}
	{!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('media/js/TableTools.js') !!}
    {!! HTML::script('media/js/ZeroClipboard.js') !!}
@stop

@section('content')
<h2>All Sponsorships</h2>
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	
	<table id="sponsorships_table" class="table">
		<thead>
			<tr>
				<th>Donor Name</th>
				<th>Sponsored Name</th>
				<th>Created</th>
				<th>Updated</th>
			</tr>
		</thead>
		<tbody>
	@foreach ($sponsorships as $s)
		<tr>
			<td>{!! $s['donor']['name'] !!}</td>
			<td>{!! $s['entity']['name'] !!}</td>
			<td>{!! Carbon::createFromTimeStamp(strtotime($s['created_at']))->toFormattedDateString() !!}</td>
			<td>{!! Carbon::createFromTimeStamp(strtotime($s['updated_at']))->toFormattedDateString() !!}</td>
		</tr>
	@endforeach
		</tbody>
	</table>
@stop

@section('footerscripts')
	<script>
	$(document).ready( function () {
	
		$('#sponsorships_table').dataTable( {
			"bStateSave" : true,
			"sDom": 'T<"clear">lfrtip',
			"oTableTools" : {
			    "sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}"
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
			}
		});
	  
	});
	</script>
@stop