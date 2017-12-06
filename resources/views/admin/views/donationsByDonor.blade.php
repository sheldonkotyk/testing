@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
	{!! HTML::style('css/demo_table.css') !!}
	{!! HTML::style('media/css/TableTools.css') !!}
	{!! HTML::script('js/jquery.dataTables.js') !!}
    {!! HTML::script('media/js/TableTools.js') !!}
    {!! HTML::script('media/js/ZeroClipboard.js') !!}
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	
@stop

@section('content')

	<h1><small><a href="{!!URL::to('admin/show_all_donors',array($hysform->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $hysform->name !!} </a></small>
	<div class="btn-group"><a href="{!! URL::to('admin/add_donor', array($hysform->id)) !!}">

            <button type="button" class="btn btn-default">
               <span class="glyphicon glyphicon-plus"></span> Add Donor
            </button></a></div>
	</h1>
	<h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $name['name'] !!} <small><span class="glyphicon glyphicon-usd"></span> <em>Donations</em>@if(isset($donor)&&$donor['deleted_at']!=null) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small></h1>
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
    
	@foreach ($errors->all() as $message)
		<div class="alert alert-danger">
			<ul>
				<li>{!! $message !!}</li>
			</ul>
		</div>	
	@endforeach


	@include('admin.views.donorMenu')
<div class="app-body">
<!-- app content here -->
<div class="magic-layout">

	<div class="panel panel-default magic-element">
        
        <div class="panel-heading">
            <div class="panel-icon"><i class="glyphicon glyphicon-usd"></i></div>
           	<div class="panel-actions">
                		<div class="label label-success">New Donation</div>
            </div>
            <h3 class="panel-title">Add Extra Donation</h3>
        </div><!-- /panel-heading -->
        
        <div class="panel-body">
			<p class="help-block">
			May be applied to a particular designation or entity. This is usually for 'over and above' donations and will not affect the payment status of a monthly commitment.
			</p>
			{!! Form::open(array('url' => 'admin/add_donation/'.$donor->id.'')) !!}
				<div class="form-group">
					{!! Form::label('created_at', 'Date') !!}
					{!! Form::text('created_at', $value = null, array('class' => 'form-control datepicker', 'placeholder' => 'Format date YYYY-MM-DD')) !!}
					<p class="help-text">Leave blank for today's date.</p>
				</div>
				
				<div class="form-group">
					{!! Form::label('designation', 'Designation') !!}
					<select id="desig" class="form-control" name="designation">
						<option></option>
						@if (!empty($sponsorships))
							@foreach ($sponsorships as $s)
							@if($s['frequency']=='Monthly')
								<option data-amount="" data-type="1" value="entity-{!! $s['id'] !!}">{!! $s['name'] !!} - {!! $s['currency_symbol'] !!}{{ $s['commit'] }}</option>
							@else
								<option data-amount="" data-type="1" value="entity-{!! $s['id'] !!}">{!! $s['name'] !!} - {!! $s['currency_symbol'] !!}{{ $s['commit'] }} monthly (Paid {!!$s['frequency']!!} = {!! $s['currency_symbol'] !!}{{$s['frequency_total']}})</option>
							@endif
							@endforeach
						@endif
						@if (!empty($designations))
							@foreach ($designations as $d)
							<option data-amount="0.00" data-type="2" value="desig-{!! $d->id !!}">{!! $d->name !!}</option>
							@endforeach
						@endif
					@if (!empty($funding_entities))
						@foreach ($funding_entities as $f)
						<option data-amount="0.00" data-type="3" value="funding-{!! $f['id'] !!}">{!! $f['name'] !!} (funding program one-time donation)</option>
						@endforeach
					@endif
					</select>
				</div>
				
				<div id="frequency" class="form-group"></div>
				
				<div id="until_date" class="form-group"></div>
				
				<div class="form-group">
					{!! Form::label('amount', 'Amount') !!}
					{!! Form::text('amount', $value = null, array('class' => 'form-control', 'placeholder' => 'Format number as 10.00')) !!}
					 {!! $errors->first('amount', '<p class="text-danger">:message</p>') !!}
					<p class="help-block">Amount must be formatted to two decimal places like 10.00 not 10.</p>
				</div>
				
				<div class="form-group">
					{!! Form::label('method', 'Method') !!}
					{!! Form::select('method', $dntns->getMethods(), null, array('class' => 'form-control')) !!}
				</div>
				
				<div id="arb_subscription" class="form-group"></div>

				<div id="cc-warning" class="form-group"></div>
				
				<div id="cc-form" class="form-group"></div>
				
				<div class="form-group">
					{!! Form::label('result', 'Result (note)') !!}
					{!! Form::textarea('result', $value = null, array('class' => 'form-control hysTextarea')) !!}
				</div>

				<div class="form-group">
					{!! Form::checkbox('dont_notify', '1' ) !!}
					{!! Form::label('dont_notify', " Don't Send an Email Receipt to Donor.") !!}
				</div>

				
				{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
			{!! Form::close() !!}
		</div>
	</div> 

	<div class="panel panel-default magic-element">
	        <div class="panel-heading">
	            <div class="panel-icon"><i class="glyphicon glyphicon-credit-card"></i></div>
	           	<div class="panel-actions">
	           			@if($anyDonorCardActive)
	                		<div class="label label-success">Credit Card Saved</div>
	                	@else
	                		<div class="label label-warning">No Credit Card</div>
	                	@endif
	            </div>
	            <h3 class="panel-title">Credit Card info</h3>
	        </div><!-- /panel-heading -->
	        <div class="panel-body">
		<ul class="nav nav-pills">
		
		@if($useCC)
			@if($donorCardActive)
			<li><div class="btn-group"><a href="#" data-toggle="modal" data-target="#update-cc">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-plus"></span> Update Credit Card
	            </button></a></div></li>
			
			<li><div class="btn-group"><a href="#" data-toggle="modal" data-target="#delete-cc">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-remove"></span> Delete Credit Card
	            </button></a></div></li>

			@else
				 <li><div class="btn-group"><a href="{!! URL::to('admin/add_cc', array($donor->id)) !!}">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-plus"></span> Add Credit Card
	            </button></a></div></li>
				@if(!empty($donor->stripe_cust_id))
					<li class="alert alert-warning"><span class="glyphicon glyphicon-credit-card"></span> Credit Card saved in Stripe.
					@if($anyDonorCardActive)
						<p>Though you are using Authorize, this card will be charged automatically via Stripe.</p>
						<p>To add a Card for Authorize instead, Simply click <a href="{!! URL::to('admin/add_cc', array($donor->id)) !!}">"Add Credit Card"</a> and the Stripe card will be deleted.</p>
					@endif
					</li>
				@elseif(!empty($donor->authorize_profile))
					<li class="alert alert-warning"><span class="glyphicon glyphicon-credit-card"></span> Credit Card saved in Authorize.
					@if($anyDonorCardActive)
						<p>Though you are using Stripe, this card will be charged automatically via Authorize.</p>
						<p>To add a Card for Stripe instead, Simply click <a href="{!! URL::to('admin/add_cc', array($donor->id)) !!}">"Add Credit Card"</a> and the Authorize card will be deleted.</p>
					@endif
					</li>
				@else
					
				@endif
			@endif
		@else
			<li><a href="{!!URL::to('admin/edit_client_account')!!}">Credit Card processing is not setup in your account settings.</a></li>
		@endif

		</ul>
		<br/>
		@if($donorCardActive)
			<span class="alert alert-success"><span class="glyphicon glyphicon-credit-card"></span> Credit Card saved in {!!ucfirst($useCC)!!}. </span>
		@else
			<span class="alert alert-warning"><span class="glyphicon glyphicon-credit-card"></span> No Credit Card in {!!ucfirst($useCC)!!}. </span>
		@endif
		</div>
	</div>		


			<div class="panel panel-default magic-element">
		        <div class="panel-heading">
		            <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
		           	<div class="panel-actions">
		                <span class="badge">{!!count($sponsorships) + count($commitments)!!} </span>
		            </div>
		            <h3 class="panel-title">Current Commitments</h3>
		        </div><!-- /panel-heading -->
		        <div class="panel-body">

				@foreach ($sponsorships as $s)
					@if(!empty($s['commitment_id']))
						<p>

						@if($s['commit']=='0.00')
							<a class="btn btn-default btn-xs pull-right" data-toggle="modal" href="{!! URL::to('admin/edit_commitment', array($s['commitment_id'])) !!}" data-target="#modal" title="Edit"> <span class="glyphicon glyphicon-pencil"></span></a>
							<a data-toggle="modal" href="{!! URL::to('admin/edit_commitment', array($s['commitment_id'])) !!}" data-target="#modal" title="Edit"><span class="pull-right alert alert-warning">Warning: Empty Commitment</span></a>

						@else
							<span class="pull-right">
							<a class="btn btn-default btn-xs pull-right" data-toggle="modal" href="{!! URL::to('admin/edit_commitment', array($s['commitment_id'])) !!}" data-target="#modal" title="Edit"><span class="glyphicon glyphicon-pencil"></span> Edit Commitment</a>
							<br><small><a href="{!! URL::to('admin/commitment_donation', array($s['commitment_id'])) !!}" class="pull-right btn-xs btn-primary"><span class="glyphicon glyphicon-plus"></span>  add payment</a></small>
							</span>
						@endif
						
							@if($s['frequency']=='Monthly')
							{!! $s['name'] !!} for {!! $s['currency_symbol'] !!}{{ sprintf("%01.2f", $s['commit']) }} per Month 

							@else
							{!! $s['name'] !!} for {!! $s['currency_symbol'] !!}{{ sprintf("%01.2f", $s['commit']) }} per Month (Paid {!! $s['frequency'] !!})

							@endif


							@if ($s['until'] != '0000-00-00')
							<br>until {!! Carbon::createFromTimeStamp(strtotime($s['until']))->toFormattedDateString() !!}.
							@endif
							<br/><small><strong>{!!$s['next']!!}</strong></small>
						</p>
						<hr>
						<?php $currency_symbol = $s['currency_symbol']; ?>
					@else
					<p>
						<!-- <span class="pull-right">
						<a class="btn btn-default btn-xs pull-right" data-toggle="modal" href="{!! URL::to('admin/edit_commitment', array($s['commitment_id'])) !!}" data-target="#modal" title="Edit"><span class="glyphicon glyphicon-pencil"></span></a>
						<br><small><a href="{!! URL::to('admin/commitment_donation', array($s['commitment_id'])) !!}" class="pull-right">add payment</a></small>
						</span> -->
						
							{!! $s['name'] !!} <a href="{!! URL::to('admin/fix_commitment', array($s['donor_entity_id'])) !!}"><span class="alert alert-warning pull-right">Fix Sponsorship with no Commitment. <span class="glyphicon glyphicon-refresh alert-warning"></span></span> </a>

							@if ($s['created'] != '0000-00-00')
							<br>created {!! Carbon::createFromTimeStamp(strtotime($s['created']))->toFormattedDateString() !!}.
							@endif
							<br/><small><strong>{!!$s['next']!!}</strong></small>
						</p>
						<hr>
						<?php $currency_symbol = $s['currency_symbol']; ?>
					@endif
				@endforeach
				
				@foreach ($commitments as $c)
					@if ($c['until'] == '0000-00-00' OR $c['until'] > Carbon::now())
					
						<p>
						
						<span class="pull-right">
						<a class="btn btn-default btn-xs pull-right" data-toggle="modal" href="{!! URL::to('admin/edit_commitment', array($c['id'])) !!}" data-target="#modal" title="Edit"><span class="glyphicon glyphicon-pencil"></span></a>
						<br><small><a href="{!! URL::to('admin/commitment_donation', array($c['id'])) !!}" class="pull-right">add payment</a></small>
						</span>
						
						@foreach ($designations as $d)
							@if ($d->id == $c['designation'])
							{!! $d->name !!} for {!! $currency_symbol or '$' !!}{{ sprintf("%01.2f", $c['commit']) }} ({!! $c['frequency'] !!})
							@endif
						@endforeach
						@if ($c['until'] != '0000-00-00')
						<br>until {!! Carbon::createFromTimeStamp(strtotime($c['until']))->toFormattedDateString() !!}.
						@endif
						<br/><small><strong>{!!$c['next']!!}</strong></small>
						</p>
						<hr>
					@endif
				@endforeach
			</div>
		</div>

	<div class="panel panel-default magic-element width-full">
		        <div class="panel-heading">
		            <div class="panel-icon"><i class="glyphicon glyphicon-usd"></i></div>
		           	<div class="panel-actions">
		                <span class="badge">{!!count($donations)!!} </span>
		            </div>
		            <h3 class="panel-title">Donation History</h3>
		        </div><!-- /panel-heading -->
		        <div class="panel-body">
					<p><em>* Click the amount to edit the donation</em></p>
					<table id="donations_table" class="table table-striped">
						<thead>
							<th>Date</th>
							<th>Type</th>
							<th>Designation Code</th>
							<th>Designation</th>
							<th>Method</th>
							<th>Result</th>
							<th>Amount</th>
						</thead>
						<tbody>
							@foreach ($donations as $d)
							<tr>
								<td>{!! Carbon::createFromTimeStamp(strtotime($d['date']))->toFormattedDateString() !!}</td>
								<td>{!! $d['type'] !!}</td>
								<td>{!! $d['code'] !!}</td>
								<td>{!! $d['designation'] !!}</td>
								<td>{!! $d['method'] !!}</td>
								<td>{!! $d['result'] !!}</td>
								<td><a title="Edit Donation" href="{!! URL::to('admin/edit_donation', array($d['id'])) !!}">{!! sprintf("%01.2f", $d['amount']) !!}</a></td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
</div>
</div>
@stop

@section('modal')	
		<!-- Edit Commitment Modal -->
  <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	    </div>
	  </div>
  </div>

		<!-- Update Credit Card Modal -->
  <div class="modal fade" id="update-cc" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title">Update Credit Card</h4>
	      </div>
	      <div class="modal-body">
	        {!! Form::open(array('url' => 'admin/update_cc/'.$donor->id.'')) !!}
	        	<div class="form-group">
					<label for="firstName">First Name</label>
					<input class="form-control" type="text" name="firstName" placeholder="First Name">
	        	</div>
	        	
	        	<div class="form-group">
					<label for="lastName">Last Name</label>
					<input class="form-control" type="text" name="lastName" placeholder="Last Name">
	        	</div>
	        	
	        	<div class="form-group">
					<label for="number">Credit Card Number</label>
					<input class="form-control" type="text" name="number" placeholder="Enter Credit Card Number">
	        	</div>
	        	
	        	<div class="form-group">
					<div class="row">
						<div class="col-xs-2">
							<label for="expiryMonth">Month</label>
							<input class="form-control" type="text" name="expiryMonth" placeholder="MM">
						</div>
	
						<div class="col-xs-2">
							<label for="expiryYear">Year</label>
							<input class="form-control" type="text" name="expiryYear" placeholder="YYYY">	        
						</div>
						
						<div class="col-xs-2">
							<label for="cvc">CVV</label>
							<input class="form-control" type="text" name="cvv" placeholder="CVV">	
						</div>
					</div>	
	        	</div>		
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	        {!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
			{!! Form::close() !!}
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
  </div>
  
		<!-- Delete Credit Card Modal -->
  <div class="modal fade" id="delete-cc" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title">Delete Credit Card</h4>
	      </div>
	      <div class="modal-body">
	        <p>Are you sure? This cannot be undone and any future donations will not be charged (of course you can always add a new card after deleting this one).</p>
	      </div>
	      <div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	        <a href="{!! URL::to('admin/delete_cc', array($donor->id)) !!}" class="btn btn-danger">Delete Card</a>
	      </div>
	    </div><!-- /.modal-content -->
	  </div><!-- /.modal-dialog -->
  </div>
	
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		// destroy modal when hidden
		$('body').on('hidden.bs.modal', '#modal', function () {
		  $(this).removeData('bs.modal');
		});	
		
		$('.hysTextarea').redactor();
		
		$(document).on("focus", "input.datepicker", function() {
			$(this).datepicker({ dateFormat: "yy-mm-dd" });
		});
		
		$('#created_at').datepicker({ dateFormat: "yy-mm-dd" });
		
		$('#desig').change(function() {
			var selected = $(this).find('option:selected');
			var a = selected.data('amount'); 
			$('input#amount').val( a );
			
			var type = selected.data('type');
			if ( type == 2 ) {
				$("div#frequency").html('<label for="frequency">Donation Frequency</label><select class="form-control" id="frequency" name="frequency"><option value="0">One Time</option><option value="1">Monthly</option><option value="2">Quarterly</option><option value="3">Semiannually</option>><option value="4">Annually</option></select>');
				$("div#until_date").html('<label for="until">Continue Until</label><input class="form-control datepicker" type="text" name="until" id="until" placeholder="YYYY-MM-DD"><p class="help-text">Leave blank for one-time donation or perpetual recurring donation</p>');
			} else {
				$("div#recurring").html('');
				$("div#until").html('');
			}
			$('.magic-layout').isotope('reLayout');
		});


		$('#method').change(function() {
			var selected = $(this).val();
			var useCC =  '{!! $useCC !!}';
			var donorCardActive = '{!! $donorCardActive !!}';
			
			if ( selected == 3 ) 
			{
				$('div#cc-warning').html('<span class="label label-info">Note: This will Charge the Donor\'s Credit Card.</span><br/> {!!Form::checkbox('dont_charge','1')!!}{{Form::label('dont_charge', 'Do Not Charge Credit Card.')}}');
				if ( useCC != false ) {
					if ( donorCardActive == false ) {
						$('div#cc-form').html('<label for="firstName">First Name</label><input class="form-control" type="text" name="firstName" placeholder="First Name"><label for="lastName">Last Name</label><input class="form-control" type="text" name="lastName" placeholder="Last Name"><label for="number">Credit Card Number</label><input class="form-control" type="text" name="number" placeholder="Enter Credit Card Number><label for="cvc">CVV</label><input class="form-control" type="text" name="cvv" placeholder="CVV"><label for="expiryMonth">Expiration Month</label><input class="form-control" type="text" name="expiryMonth" placeholder="MM"><label for="expiryYear">Expiration Year</label><input class="form-control" type="text" name="expiryYear" placeholder="YYYY"><br/>{!!Form::checkbox("dont_charge","1")!!}{{Form::label("dont_charge", " Do Not Charge Credit Card.")}}');
					}
				}
			}
			else {
				$('div#cc-warning').html('');
				$('div#cc-form').html('');
			}

			if (selected == 5)
			{
				$('div#arb_subscription').html('{!! Form::label('arb_subscription_id', 'ARB Subscription ID') !!}{{ Form::text('arb_subscription_id', null, array('class' => 'form-control')) }}');
			}

			$('.magic-layout').isotope('reLayout');
		});
		
		$('#donations_table').dataTable( {
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
			"sProcessing" : 'Processing...<div class="progress progress-striped active"><div class="bar" style="width:100%"></div></div>'
			}
		});
		$('#donations_table').wrap('<div class="scrollStyle" />');
	});
	</script>
@stop