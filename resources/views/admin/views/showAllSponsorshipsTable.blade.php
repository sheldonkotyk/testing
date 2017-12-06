	<table id='admin_results_table_sponsorships_{!!$program_id!!}_{!!$trashed!!}' class="table table-bordered">
		<thead>
			<tr>

			<th>ID</th>
			@if ($manage == true&&empty($program->link_id))
				<th>Manage</th>
			@endif

			@if($program_id=='all')
			<th class="text-info">Program</th>
			@endif

			@if ($thumb == true)
				<th class="text-info">Profile Photo</th>
			@endif
		
			@foreach ($programFields as $pfield)
			
				@if (!empty($pfield->field_label))
					<th class="text-info visible">{!! $pfield->field_label !!}</th>
				@else 
					<th></th>
				@endif
				
			@endforeach
		
			<!-- @foreach($details_display as $name =>$display)
				<th class="text-info visible dt-head-nowrap">{!! $display !!}</th>
			@endforeach -->
			
			@if ($created_at == true)
				<th class="text-info visible dt-head-nowrap">Added</th>
			@endif
		
			@if ($updated_at == true)
				<th class="text-info visible dt-head-nowrap">Updated</th>
			@endif
		
			@foreach ($donorFields as $dfield)
				@if (!empty($dfield->field_label))
					<th class="text-success visible" >{!! $dfield->field_label !!}</th>
				@else
					<th></th>
				@endif
			@endforeach
			
		
			@if($email == true)
				<th class="text-success visible dt-head-nowrap">Email</th>
			@endif
		
			@if($username == true)
				<th class="text-success visible dt-head-nowrap">Username</th>
			@endif

			@if($amount == true)
				<th class="text-success visible dt-head-nowrap">Amount</th>
			@endif

			@if($frequency == true)
				<th class="text-success visible dt-head-nowrap">Frequency</th>
			@endif

			@if($until == true)
				<th class="text-success visible dt-head-nowrap">End Date</th>
			@endif

			@if($last == true)
				<th class="text-success visible dt-head-nowrap">Last Payment</th>
			@endif

			@if($next == true)
				<th class="text-success visible dt-head-nowrap">Next Payment</th>
			@endif

			@if($method == true)
				<th class="text-success visible dt-head-nowrap">Method</th>
			@endif
		
			@if ($donor_created_at == true)
				<th class="text-success visible dt-head-nowrap">Donor Added</th>
			@endif
		
			@if ($donor_updated_at == true)
				<th class="text-success visible dt-head-nowrap">Donor Updated</th>
			@endif
		
			@if ($sponsorship_created_at == true)
				<th class="text-success visible dt-head-nowrap">Sponsorship Created</th>
			@endif
	
	
			</tr>
		</thead>

	</table>
	
	<script>
	$(document).ready( function () {
	
		$('#admin_results_table_sponsorships_{!!$program_id!!}_{!!$trashed!!}').dataTable( {
			"stateSave" : true,
			"ajax": "{!!URL::to('admin/show_all_sponsorships_ajax',array($program_id,$trashed))!!}",
			"deferRender": true,
			"columnDefs": [
		        {
		            "targets": [ 0 ],
		            "visible": false,
		            "searchable": true
		        }
		    ],
			"dom": 'T<"clear">lfrtip',
		    "tableTools": {
		    	// "sRowSelect": "multi",
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
			                    "sButtonText": "<span class=\"glyphicon glyphicon-file\"></span> Csv",
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
		
		$('#admin_results_table_sponsorships_{!!$program_id!!}_{!!$trashed!!}').wrap('<div class="scrollStyle" />');
	  
	});
	</script>
