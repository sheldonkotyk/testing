@extends('frontend.default')

@section('headerscripts')
	{!! HTML::style('DataTables-1.10.0/media/css/jquery.dataTables.css') !!}
	{!! HTML::style('DataTables-1.10.0/extensions/TableTools/css/dataTables.tableTools.css') !!}
	{!! HTML::script('DataTables-1.10.0/media/js/jquery.dataTables.js') !!}
    {!! HTML::script('DataTables-1.10.0/extensions/TableTools/js/dataTables.tableTools.js') !!}

@stop

@section('content')

<div style="width: 60%;" class="pull-left">
	@if(!empty($text_account))
		<div class="panel panel-default panel-body">
			{!!$text_account!!}
		</div>
	@endif
	@if(!empty($sponsorships))
	<div class="panel panel-default">
		<div class="panel-heading">
   			<h3 class="panel-title">My Sponsorships</h3>
  		</div>
	@foreach($sponsorships as $key => $sponsorship)
	<?php
		$temp_e=Entity::find($sponsorship['id']);
		$temp_p_id=null;
		if($temp_e!=null)
			$temp_p_id= $temp_e->program_id;
	?>
	<div class="panel-body">

		<div style="width: 20%;" class="pull-right">	
		@if(isset($sponsorship['funding_percent'])&&$sponsorship['funding_percent']!='100'&&$temp_p_id!=null)
  			<a href="{!!URL::to('frontend/view_entity',array($client_id,$temp_p_id,$sponsorship['id'],$session_id))!!}">
  		@else
			<a href="{!!URL::to('frontend/donor_view_entity',array($client_id,$program_id,$sponsorship['id'],$session_id))!!}">	
		@endif
				<img src="{!! $profilePics[$key] !!}" class="img-rounded img-responsive"/>
			</a>
		</div>
		<?php $e_name=$donor->getEntityName($sponsorship['id'],$redis,'donor'); ?>
  		<span class="pull-left"><strong>{!!$e_name['name']!!}</strong> </span>
  		<br/>

  		@if(isset($sponsorship['entity_info']))
  		{!! $sponsorship['entity_info'] !!} |
  		@endif


  		@if(isset($sponsorship['entity_percent']))
	      <div class="progress" style="max-width:50%;">
	        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {!!$sponsorship['entity_percent']!!}%;">
	          	{!!$sponsorship['entity_percent']!!}%
	        </div>
	      </div>
  		@endif

  		@if($hide_payment!='hidden')
  		@if($sponsorship['frequency']=='Monthly')
  			<span>{!!$sponsorship['currency_symbol']!!}{{$sponsorship['commit']}} {!!$sponsorship['frequency']!!} <em>(via {!!$sponsorship['method']!!})</em> @if($can_donor_modify_amount=='1')<a href="{!!URL::to('frontend/modify_amount',array($client_id,$program_id,$sponsorship['commitment_id'],$session_id))!!}"><small>Modify</small></a>@endif</span>
  		@else
  			<span>{!!$sponsorship['currency_symbol']!!}{{$sponsorship['commit']}} per Month <em>(paid {!!$sponsorship['frequency']!!} via {!!$sponsorship['method']!!})</em> @if($can_donor_modify_amount=='1')<a href="{!!URL::to('frontend/modify_amount',array($client_id,$program_id,$sponsorship['commitment_id'],$session_id))!!}"><small>Modify</small></a>@endif</span>
  		@endif
  		@if(isset($sponsorship['status']) && $sponsorship['status']=='0'&&$temp_p_id!=null)
  			 	<a href="{!!URL::to('frontend/view_entity',array($client_id,$temp_p_id,$sponsorship['id'],$session_id))!!}">Give Again</a>
  		@endif
  		
  		<br/>
  		<span class="pull-left">
  				<strong>{!!$sponsorship['next']!!}</strong>
  		</span>
  		<br/>
  		@endif
  		<span class="pull-left">
  			<a href="{!!URL::to('frontend/donor_view_entity',array($client_id,$program_id,$sponsorship['id'],$session_id))!!}">
  				View Profile
  			</a>
  		</span>
  		<br/>
  		@if($sponsorship['allow_email']==1)
  		<span class="pull-left">
  			<a href="{!!URL::to('frontend/donor_view_entity_messages',array($client_id,$program_id,$sponsorship['id'],$session_id))!!}">
  				View Correspondence
  			</a>
  		</span>

  		<br/>
  		<span class="pull-left">
  			<a href="{!!URL::to('frontend/donor_view_entity_compose_message',array($client_id,$program_id,$sponsorship['id'],'0',$session_id))!!}">
  				Send Message
  			</a> |
  			<a href="{!!URL::to('frontend/donor_upload',array($client_id,$program_id,$sponsorship['id'],$session_id))!!}">
  				Upload File
  			</a>
  		</span>
  		
  		@endif
  		

  		
  	</div>

	@endforeach
	</div>
	@endif

	@if(!empty($funded_entities))
	<div class="panel panel-default">
		<div class="panel-heading">
   			<h3 class="panel-title">My Recipients</h3>
  		</div>
	@foreach($funded_entities as $key => $e)
	<div class="panel-body">
		<div style="width: 20%;" class="pull-right">	
			<a href="{!!URL::to('frontend/view_entity',array($client_id,$program_id,$key,$session_id))!!}">	
				<img src="{!! $profilePics[$key] !!}" class="img-rounded img-responsive"/>
			</a>
		</div>
		<?php $e_name=$donor->getEntityName($key,$redis,'donor'); ?>
  		<span class="pull-left"><strong>{!!$e_name['name']!!}</strong> </span>
  		<br/>
  		
	      <p><span>{!! $e['funding_info'] !!} 
	      		@if($e['funding_percent']!='100')
  					| <a href="{!!URL::to('frontend/view_entity',array($client_id,Entity::find($key)->program_id,$key,$session_id))!!}">Give Again</a>
  				@endif
  			</span>
  		  </p>
	      <div class="progress" style="max-width:50%;">
	        <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {!!$e['funding_percent']!!}%;">
	          	{!!$e['funding_percent']!!}%
	         	
	        </div>
	      </div>
  		
  		@if($allow_email==1)
  		<span class="pull-left">
  			<a href="{!!URL::to('frontend/donor_view_entity_messages',array($client_id,$program_id,$key,$session_id))!!}">
  				View Correspondence
  			</a>
  		</span>

  		<br/>
  		<span class="pull-left">
  			<a href="{!!URL::to('frontend/donor_view_entity_compose_message',array($client_id,$program_id,$key,$session_id))!!}">
  				Send Message
  			</a> |
  			<a href="{!!URL::to('frontend/donor_upload',array($client_id,$program_id,$key,$session_id))!!}">
  				Upload File
  			</a>
  		</span>
  		@endif
  		

  		
  	</div>

	@endforeach
	</div>
	@endif

	@if(!empty($commitments))

		<div class="panel panel-default"  >
		<div class="panel-heading">
   			<h3 class="panel-title">My Commitments</h3>
  		</div>
	@foreach($commitments as $key => $commitment)
	<div class="panel-body">
		
  		<span class="pull-left"> <strong>{!!$commitment['name']!!} </strong></span>
  		<span class="pull-right">
  				 ${!!$commitment['commit'] !!} <em>({!!$commitment['frequency']!!} via {!!$commitment['method']!!})</em>  @if($can_donor_modify_amount=='1')<a href="{!!URL::to('frontend/modify_amount',array($client_id,$program_id,$commitment['id'],$session_id))!!}"><small>Modify</small></a>@endif
  			</span>
  		
  	</div>

	@endforeach
	</div>
	@endif


	
</div>
<div style="width: 35%;" class="pull-right">	
	<div class="panel panel-default"  >
		<div class="panel-heading">
   			<h3 class="panel-title">My Info </h3>
   			<a class="pull-right btn btn-default" href="{!!URL::to('frontend/donor_update_info',array($client_id,$program_id,$session_id))!!}"> Update My Info </a>
	  		<div class="clearfix"></div>
  		</div>
  	<div class="panel-body">
  		Username
  		<span class="pull-right">{!!$username!!}</span>
  	</div>
  	<div class="panel-body">
  		Email Address
  		<span class="pull-right">{!!$email!!}</span>
  	</div>

	@foreach ($fields as $field)
		

		@if (isset($profile[$field->field_key]))
			<div class="panel-body">
				{!! $field->field_label !!} 
				<span class="pull-right">{!! $profile[$field->field_key] !!}</span>
			</div>
		@else
		
		@endif
		
	@endforeach

  	<!-- <div class='panel-body'>
		<div class="pull-right">
	  			<a href="{!!URL::to('frontend/donor_update_info',array($client_id,$program_id,$session_id))!!}">	
				<button type="button" class="btn btn-default">
				   <span class="glyphicon glyphicon-info-sign"></span> Update My Info
				   </button>
				</a>
		</div>
	</div> -->

@if(isset($credit_card)&&$useCC)
  	<div class="panel-body">
  		Credit Card <strong>{!!$credit_card!!}</strong>
  		<span class="pull-right">
  			<a href="{{ URL::to('frontend/donor_update_card/'.$client_id.'/'.$program_id.'/'.$session_id) }}">	
			<button type="button" class="btn btn-default">
			   <span class="glyphicon glyphicon-credit-card"></span> {!!($credit_card=='[None]' ? 'Add' : 'Update' )!!} Card
			   </button>
			</a>
  		</span>
  	</div>
  	@endif



	<!-- <div class="panel-body">
		My files
		<div class="pull-right">

			<a href="{{ URL::to('frontend/donor_upload/'.$client_id.'/'.$program_id.'/'.$session_id) }}">	
			<button type="button" class="btn btn-default">
			   <span class="glyphicon glyphicon-upload"></span> Upload Files
			</button>
			</a>
			
		</div>
	</div> -->


	</div>
	</div>

	@if($allow_donations=='1'&&($hide_payment!='hidden'))
		<div class="panel panel-default pull-left" style="width:60%;">
		<div class="panel-heading">
   			<h3 class="panel-title">My Donations</h3>
  		</div>
  			<table id='donation_table' class="table table-bordered">
  			<thead>
  			<tr>
  			<th>Date</th>
  			<th>{!!$currency!!} Amount</th>
  			<th>Name</th>
  			</tr>
  			</thead>
  			<tbody>
			@foreach($donations as $id => $donation)
				
				<tr>
					<td>
						  {!!Carbon::createFromTimeStamp(strtotime($donation->created_at))->toFormattedDateString()!!}
					</td>
					<td>
						{!!number_format($donation->amount,2,'.','')!!}
					</td>
					<td>
						@if (!empty($d_names[$id]))
						{!!$d_names[$id]!!}
						@endif
					</td>
					
				</tr>

			@endforeach
			</tbody>
	</table>
	<br/>
	<br/>
		</div>
	@endif


<script>
$(document).ready( function () {

	$('#donation_table').dataTable( {
		"bStateSave" : false,
		 "aoColumns": [
   			 { "sType":"date"},
 		     { "sType":"numeric"},
    		 { "sType": "string"}], //THIS IS THE DATE
    	"order": [[ 0, "desc" ]],
		"sDom": 'lrtip',
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