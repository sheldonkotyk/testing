<table id='admin_results_table_hysform_{!!$hysform_id!!}_{!!$trashed!!}' class="table display table-bordered ">
<thead>
<tr>

<th class="alert alert-success">Donor ID</th>
@if ($manage == true)
	<th class="alert alert-success dt-head-nowrap">Manage</th>
@endif
@if ($thumb == true)
	<th class="alert alert-success visible dt-head-nowrap">Profile Photo</th>
@endif

@foreach ($fields as $field)
	<th class="alert alert-success visible dt-head-nowrap">{!! $field->field_label !!}</th>
@endforeach

@if($email == true)
	<th class="alert alert-success visible dt-head-nowrap">Email</th>
@endif

@if($username == true)
	<th class="alert alert-success visible dt-head-nowrap">Username</th>
@endif

@foreach($details_display as $name =>$display)
	<th class="alert alert-success visible dt-head-nowrap">{!! $display !!}</th>
@endforeach

@if ($created_at == true)
	<th class="alert alert-success visible dt-head-nowrap">Date Added</th>
@endif

@if ($updated_at == true)
	<th class="alert alert-success visible dt-head-nowrap">Date Updated</th>
@endif
</tr>
</thead>
</table>

<script>


$(document).ready( function () {
	

	 var nowrap = {!!$nowrap!!};

	 var table = $('#admin_results_table_hysform_{!!$hysform_id!!}_{!!$trashed!!}').DataTable( {
	 	"stateSave" : true,
		"ajax": "{!!URL::to('admin/show_all_donors_ajax',array($hysform_id,$trashed))!!}",
		"pagingType": "full_numbers",
		"deferRender" : true,
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
                "searchable": false
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
		                    "sButtonText": "<span class=\"glyphicon glyphicon-file\"></span> CSV",
		                     "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
			            },		                
		                {
		                    "sExtends": "pdf",
		                    "sButtonText": "<span class=\"glyphicon glyphicon-file\"></span> PDF",
		                     "mColumns": function ( ctx ) 
		                    {
			                    var api = new $.fn.dataTable.Api( ctx );
			 
			                    return api.columns( '.visible' ).indexes().toArray();
			                }
		                },
		                {
		                    "sExtends": "print",
		                    "sButtonText": "<span class=\"glyphicon glyphicon-print\"></span> Print"
		                }

		            ]
                }
            ]
		},
		"oLanguage" : {
		"sEmptyTable":  "No <strong>{!!(empty($trashed) ? "" : "Archived")!!}</strong> Donors found. {!!(!empty($trashed) ? '' : '<a href ='.URL::to("admin/add_donor",array($hysform_id)).'>Click to add a Donor</a>')!!}",
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

	var tt = TableTools.fnGetInstance( 'admin_results_table_hysform_{!!$hysform_id!!}_{!!$trashed!!}' );

	$( '#admin_results_table_hysform_{!!$hysform_id!!}_{!!$trashed!!}' ).on('click','tr', function(event) {


	  var selected = tt.fnGetSelected().length;
	  var s ='s';

	  if(selected == 1)
	  	s='';

	  if(selected<1)
	  	$('span#selected').html("<strong>All {!!$number_of_donors!!} Donors</strong>");
	  else
	  	$('span#selected').html("<strong>"+selected+" Selected Donor"+s+"</strong>");

	});

	

	

	var msgOpt = function(place, theme){
            Messenger.options = {
                extraClasses: 'messenger-fixed ' + place,
                theme: theme
            }
        }

	@foreach($years as $year)

    $('#statement_{!!$year!!}').click( function () {
    	var array = [];

    	table.rows('.selected').data().each(function (value,index)
    	{
  			array.push(value[0]);
    	});

  		$.ajax({
			url: "{!! URL::to('admin/send_year_end_donors',array($hysform_id,$emailsets['default_emailset']['id'],$year)) !!}",
			data: { 'donor_ids':array },
			cache: 'false',
			dataType: 'html',
			type: 'post',
			success: function(html, textStatus) {
 						
 						html = JSON.parse(html);
 						
 						var place = 'messenger-on-top',
		                theme = 'flat';

		                msgOpt(place, theme);

						var success_message = html['success_message'];
						var error_message = html['error_message'];

			            if(error_message)
			            {
			              Messenger().post({message: error_message ,
			              	hideAfter: 500,
			            	type: "error",
			            	showCloseButton: true});
			          	}
			          	 if(success_message)
		                {
				            Messenger().post({message: success_message ,
				            	hideAfter: 500,
				            	type: "success",
				            	showCloseButton: true});
			        	}

					}
		});		

        tt.fnSelectNone();
        $('span#selected').html("<strong>All {!!$number_of_donors!!} Donors</strong>");
    });

    @endforeach

     $('#notify').click( function () {
    	var array = [];
    	
    	table.rows('.selected').data().each(function (value,index)
    	{
  			array.push(value[0]);
    	});
  		$.ajax({
			url: "{!! URL::to('admin/send_notify_donors',array($hysform_id,$emailsets['default_emailset']['id'])) !!}",
			data: { 'donor_ids':array },
			cache: 'false',
			dataType: 'html',
			type: 'post',
			success: function(html, textStatus) {
						html = JSON.parse(html);
 						
 						var place = 'messenger-on-top',
		                theme = 'flat';

		                msgOpt(place, theme);

						var success_message = html['success_message'];
						var error_message = html['error_message'];

			            if(error_message)
			            {
			              Messenger().post({message: error_message ,
			              	hideAfter: 500,
			            	type: "error",
			            	showCloseButton: true});
			          	}

			          	 if(success_message)
		                {
				            Messenger().post({message: success_message ,
				            	hideAfter: 500,
				            	type: "success",
				            	showCloseButton: true});
			        	}

					}
		});		

         tt.fnSelectNone();
         $('span#selected').html("<strong>All {!!$number_of_donors!!} Donors</strong>");
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
				url: "{!! URL::to('admin/archive_donors',array($hysform_id)) !!}",
				data: { 'donor_ids':array },
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
				url: "{!! URL::to('admin/activate_donors',array($hysform_id,true)) !!}",
				data: { 'donor_ids':array },
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
				url: "{!! URL::to('admin/delete_donors',array($hysform_id)) !!}",
				data: { 'donor_ids':array },
				cache: 'false',
				dataType: 'html',
				type: 'post',
			});		
	  	}
        table.row('.selected').remove().draw(false);
    } );

	$('#admin_results_table_hysform_{!!$hysform_id!!}_{!!$trashed!!}').wrap('<div class="scrollStyle" />');


});
</script>
