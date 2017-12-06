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

    <h1><small><a href="{!!URL::to('admin/archived_report')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Progress Reports </a></small></h1>
    <h2>Progress Reports <small> <span class="icon ion-ios7-browsers"></span> {!! Carbon::createFromTimeStamp(strtotime($date_from))->toFormattedDateString() !!} to {!! Carbon::createFromTimeStamp(strtotime($date_to))->toFormattedDateString() !!} </small></h2>
	
	@include('admin.views.progressReportsMenu')

	<div class="app-body">
	<div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="icon ion-ios7-browsers"></i></div>
                <div class="panel-actions">
                    <div class="badge"></div>
                </div>
               
                <h3 class="panel-title">Results</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">
	
				<table id="donations_table" class="table table-striped">
					<thead>
						@foreach ($profile_fields as $pf)
						<th>{!! $pf->field_label !!}</th>
						@endforeach
						<th>Admin</th>
						<th>Date</th>
					</thead>
					<tbody>
					@foreach ($forms as $form)
						<tr>
						@foreach ($profile_fields as $pf)
							<?php $td = '<td></td>'; ?>
							@foreach ($form['form_info'] as $f)
								@if ($pf->field_key == $f['field_key'])
									<?php $td = '<td>'.$f['data'].'</td>'; ?>
								@endif
							@endforeach
							{!! $td !!}
						@endforeach
							<td>{!! $form['admin']->first_name !!} {!! $form['admin']->last_name !!}</td>
							<td>{!! Carbon::createFromTimeStamp(strtotime($form['form']->created_at))->toFormattedDateString() !!}</td>
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
	
		$('#donations_table').dataTable( {
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
			}
		});
	
	});
	</script>
@stop