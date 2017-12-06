
<table id="entity_results_table_program_{!!$program->id!!}_{!!$trashed!!}" class="table display table-bordered ">
<thead>
<tr>
	<th>Recipient ID</th>
@if ($manage == true&&$program->link_id=='0')
	<th class="alert alert-info dt-head-nowrap">Manage</th>
@endif

@if ($thumbnail == true)
	<th class="alert alert-info visible dt-head-nowrap">Profile Photo</th>
@endif

@if($profile_link == true)
	<th class="alert alert-info visible dt-head-nowrap">Profile Photo Link</th>
@endif

@foreach ($fields as $field)
	<th class="alert alert-info visible dt-head-nowrap">{!! $field->field_label !!}</th>
@endforeach

@if ($created_at == true)
	<th class="alert alert-info visible dt-head-nowrap">Date Added</th>
@endif

@if ($updated_at == true)
	<th class="alert alert-info visible dt-head-nowrap">Date Updated</th>
@endif

@foreach($details_display as $name =>$display)
	<th class="alert alert-info visible dt-head-nowrap">{!! $display !!}</th>
@endforeach

</tr>
</thead>

</table>
 
<script>
$(document).ready( function () {
	$('a.delete').on('click', function() {
		var entityid = $( this ).data('id');
		console.log(entityid);
		$('a.amodal').prop('href', '{!! URL::to("admin/permanently_delete_entity", array($program->id)) !!}/'+entityid );
		$('#deleteModal').modal('show');	
	});

	var nowrap = {!!$nowrap!!};

	var table = $('#entity_results_table_program_{!!$program->id!!}_{!!$trashed!!}').DataTable( {
		"stateSave" : true,
		"ajax": "{!!URL::to('admin/show_all_entities_ajax',array($program->id,$trashed))!!}",
		"deferRender": true,
		"fnRowCallback": function( nRow, aData, iDisplayIndex ) 
	    {
	        $('td', nRow).each(function (iPosition){
	        	if(nowrap[iPosition])
	        	{
	                var sCellContent = $(this).html();

	                sCellContent = '<DIV style="white-space:nowrap;">' + sCellContent + '</DIV>';
	                $(this).html(sCellContent);
	            }	
	            });
	        return nRow;
        },
		"columnDefs": [
            {
                "targets": [ 0 ],
                "visible": false,
                "searchable": true
            }
        ],
		"dom": 'T<"clear">lfrtip',
        "tableTools": {
        	"sRowSelect": "multi",
        	"sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
            "aButtons": [
                {
                    "sExtends":    "collection",
                    "sButtonText": "<span class=\"glyphicon glyphicon-save\"></span> Save",
                    "aButtons": [
		                {
		                    "sExtends": "copy",
		                    "sButtonText": "Copy to Clipboard",
		                   	 "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
		                },
		                {
		                    "sExtends": "csv",
		                    "sButtonText": "<span class=\"glyphicon glyphicon-file\"></span> CSV File",
		                     "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
			            },		                
		                {
		                    "sExtends": "pdf",
		                    "sButtonText": "<span class=\"glyphicon glyphicon-file\"></span> PDF File",
		                     "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
		                },
		                {
		                    "sExtends": "print",
		                    "sButtonText": " <span class=\"glyphicon glyphicon-print\"></span> Print"
		                }
		            ]
                }
            ]
		},
		"oLanguage" : {
		"sEmptyTable":  'No <strong>{!!ucfirst($trashed_name)!!}</strong> Recipients found. <a href ="{!!URL::to("admin/add_entity",array($program->id))!!}">Click to add a Recipient</a>',
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

	$('#archive').click( function () {
    	var array = [];
    	table.rows('.selected').data().each(function (value,index)
    	{
  			array.push(value[0]);
    	});
		if(array.length>0)
		    {
	  		$.ajax({
				url: "{!! URL::to('admin/remove_entities',array($program->id)) !!}",
				data: { 'entity_ids':array },
				cache: 'false',
				dataType: 'html',
				type: 'post',
				success: function(html, textStatus) {
							console.log(html,textStatus);
						}
			});		

	        table.row('.selected').remove().draw(false);
	    }
    });

     $('#restore').click( function () {
    	var array = [];
    	table.rows('.selected').data().each(function (value,index)
    	{
  			array.push(value[0]);
    	});

    	if(array.length>0)
    	{
	  		$.ajax({
				url: "{!! URL::to('admin/activate_entities',array($program->id)) !!}",
				data: { 'entity_ids':array },
				cache: 'false',
				dataType: 'html',
				type: 'post'
			});		

	        table.row('.selected').remove().draw(false);
    	}	
    } );

     $('#delete').click( function () {
    	var array = [];
    	table.rows('.selected').data().each(function (value,index)
    	{
  			array.push(value[0]);
    	});

    	if(array.length>0)
    	{
	  		$.ajax({
				url: "{!! URL::to('admin/delete_entities',array($program->id)) !!}",
				data: { 'entity_ids':array },
				cache: 'false',
				dataType: 'html',
				type: 'post',
				success: function(html, textStatus) {
							console.log(html,textStatus);
						}
			});		
	  	}
        table.row('.selected').remove().draw(false);
    } );
	
	$('#entity_results_table_program_{!!$program->id!!}_{!!$trashed!!}').wrap('<div class="scrollStyle" />');

});
</script>