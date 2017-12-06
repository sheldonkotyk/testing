@extends('admin.default')

@section('content')

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

<table id='admin_results_table' class="table table-striped">
    <thead>
        <tr>
            
            @foreach ($fields as $field)
            <th > {!! $field['field_label'] !!}</th>
            @endforeach
            <th>Edit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($processed as $profile)
        <tr data-template>
       
            @foreach ($fields as $field)

                <td>{!!$profile[$field['field_key']]!!}</td>
            
            @endforeach
         <td><a href="{!! URL::to('admin/edit_entity') !!}/{!!$profile['hysmanage']!!}" class="btn btn-default btn-xs">Edit</a></td>
                
        </tr>
        @endforeach
    </tbody>
</table>

<script>


$(document).ready( function () {
  $('#admin_results_table').dataTable( {
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

  } );
} );
</script>

@stop