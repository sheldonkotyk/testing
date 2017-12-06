@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}

	{!! HTML::style('DataTables-1.10.4/media/css/jquery.dataTables.css') !!}
	{!! HTML::style('DataTables-1.10.4/extensions/TableTools/css/dataTables.tableTools.css') !!}
	{!! HTML::script('DataTables-1.10.4/media/js/jquery.dataTables.js') !!}
    {!! HTML::script('DataTables-1.10.4/extensions/TableTools/js/dataTables.tableTools.js') !!}
@stop

@section('content')
	<h2>{!!Client::find(Session::get('client_id'))->organization!!} Mailgun Logs<small> <span class="icon ion-ios7-email"></span> View Mailgun logs for: {!!$emailsetting->host!!}</small></h2>

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

	
	<table id='mailgun_logs' class="table display table-bordered ">
	<thead>
	<tr>

	<th class="alert alert-success">HAP</th>
	<th class="alert alert-success">Message</th>
	<th class="alert alert-success">Type</th>
	<th class="alert alert-success">Created At</th>
	<th class="alert alert-success">Message ID</th>
	
	</tr>
	</thead>
	</table>


@stop

@section('footerscripts')

<script>
$(document).ready( function () {
	


	 var table = $('#mailgun_logs').DataTable( {
	 	"stateSave" : true,
		"ajax": "{!!URL::to('admin/mailgun_logs_data')!!}",
		"pagingType": "full_numbers",
		"dom": 'T<"clear">lfrtip',
        "tableTools": {
        	"sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
            "aButtons": [
                {
                    "sExtends":    "collection",
                    "sButtonText": "Save",
                    "aButtons": [
		                {
		                    "sExtends": "copy",
		                    "sButtonText": "Copy",
		                   	 "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
		                },
		                {
		                    "sExtends": "csv",
		                    "sButtonText": "Csv",
		                     "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
			            },		                
		                {
		                    "sExtends": "pdf",
		                    "sButtonText": "PDF",
		                     "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
		                },
		                {
		                    "sExtends": "print",
		                    "sButtonText": "Print"
		                }

		            ]
                }
            ]
		},
		"oLanguage" : {
		"sEmptyTable":  "Error: Could not access mailgun logs. Check your Mailgun API and hostname. You also may not have any emails logged yet.",
		"sLengthMenu" : 'Show <select>' +
		'<option value="100">100</option>' +
		'<option value="-1">All</option>' +
		'</select> Entries',
		"sProcessing" : 'Processing...<div class="progress progress-striped active"><div class="bar" style="width:100%;"></div></div>'
		}
	});

	$('#mailgun_logs').wrap('<div class="scrollStyle" />');


});
</script>
	
@stop