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
	

	@if($all=='1')
	
		 <h2>All {!!Client::find(Session::get('client_id'))->organization!!} Donations <small> <span class="icon ion-ios7-calculator"></span> View All Donations</small></h2>
	
	@else
	
		<h1><small><a href="{!!URL::to('admin/view_donations/1?'.$date_from)!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Donations </a></small></h1>
		<h2>{!! $hysform['name'] !!} <small> <span class="icon ion-ios7-calculator"></span> View All Donations for {!! $hysform['name'] !!}</small></h2>
	
	@endif

	@include('admin.views.donationsMenu')
	
	<div class="app-body">	
		
		@if(!empty($donation_graph_data))
		
		    <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
		            
	                <div class="panel-icon"><i class="icon ion-ios7-calculator"></i></div>
	                
	                <div class="panel-actions">
	                    <div class="label label-success">{!!$number_of_donations!!} Donations</div>
	                    <div class="label label-success">Totalling {!!$total_of_donations!!}</div>
	                </div>
	               
	                <h3 class="panel-title">Donation Graph : {!! Carbon::createFromTimeStamp(strtotime($date_from))->toFormattedDateString() !!} to {!! Carbon::createFromTimeStamp(strtotime($date_to))->toFormattedDateString() !!}</h3>
	            </div><!-- /panel-heading -->
	            
	            <div class="panel-body">
		            <div class="kits-chart">
		            	<div id="donation-chart" class="chart"></div>
			        </div><!-- /kits-chart -->
		        </div>
		    </div>
		    
		@endif
	
	
		<div class="panel panel-default magic-element width-full">
		  
	        <div class="panel-heading">
	        	<div class="panel-icon"><i class="icon ion-ios7-calculator"></i></div>
	        
                <div class="panel-actions">
                    <div class="label label-success">{!!$number_of_donations!!} Donations</div>
                    <div class="label label-success">Totalling {!!$total_of_donations!!}</div>
                </div>
	                
	            <h3 class="panel-title"> Donation Table : {!! Carbon::createFromTimeStamp(strtotime($date_from))->toFormattedDateString() !!} to {!! Carbon::createFromTimeStamp(strtotime($date_to))->toFormattedDateString() !!} </h3><!-- /pb-title -->
	        </div><!-- /panel-body-heading -->
	        
	        <div class="panel-body">
		        
		        <table id="donations_table" class="table table-striped">
			        
					<thead>
						
						@foreach ($fields as $field)
						<th>{!! $field['field_label'] !!}</th>
						@endforeach
						
						<th>Program</th>
						@if($all=='1')
							<th>Donor Name</th>
						@endif
						
						<th>Designation Code</th>
						<th>Designation</th>
						<th>Method</th>
						<th>Amount</th>
						<th>Date</th>
						
					</thead>
					
					<tbody>
						
						@foreach ($donations as $d)
						
						<tr>
							@foreach ($fields as $field)
								@if (!empty($d['profile'][$field['field_key']]))
								<td>{!! $d['profile'][$field['field_key']] !!}</td>
								@else
								<td></td>
								@endif
							@endforeach

							@if(isset($d['designation']['program_name']))
							<td>{!!$d['designation']['program_name']!!}</td>
							@else
							<td></td>
							@endif
							
							@if(isset($d['donor']['name']))
							<td><a href="{!!URL::to('admin/edit_donor/'.$d['donor']['id'])!!}">{!!$d['donor']['name']!!}</a></td>
							@endif
							
							@if (isset($d['designation']['code']))
							<td>{!! $d['designation']['code'] !!}</td>
							@else 
							<td></td>
							@endif
			
							@if($d['designation']['type']=='1')
								<td><a href="{!!URL::to('admin/edit_entity/'.$d['designation']['id'])!!}">{!! $d['designation']['name'] !!}</a></td>
							@else
								<td>{!! $d['designation']['name'] !!}</td>
							@endif
							
							<td>{!! $d['method'] !!}</td>
							<td>{!! $d['amount'] !!}</td>
							<td>{!! Carbon::createFromTimeStamp(strtotime($d['date']))->toFormattedDateString() !!}</td>
						</tr>
						
						@endforeach
						
					</tbody>
					
				</table>
			
	        </div><!-- /panel-body -->
	        
	    </div><!-- /panel -->
	
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
			},
			"fnInfoCallback": function () {
            $('.magic-layout').isotope('reLayout'); // relayout .magic-layout
        }

		});
		

        // charts
        // chart commitments

        var data2= {!!json_encode($donation_graph_data)!!};
        if (data2[0] != null)
        {
	        donationChart = Morris.Bar({
	            element: 'donation-chart',
	            data: data2,
	            barColors: ['#3498db'],
	            gridTextColor: '#34495e',
	            // pointFillColors: ['#3498db'],
	            xkey: 'dates',
	            ykeys: ['totals'],
	            labels: ['Donations'],
	            barRatio: 0.4,
	            hideHover: 'auto'
	        });
     	}

        // update data on content or window resize
        var update = function(){
            donationChart.redraw();
        }

        // handle chart responsive on toggle .content
        $(window).on('resize', function(){
            update();
        })
        
        $('#toggle-aside').on('click', function(){
            // update chart after transition finished
            $("#content").bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){
                update();
                $(this).unbind();
            });
        })
        $('#toggle-content').on('click', function(){
            update();
        })
        // end chart	
	
	});

	</script>
@stop