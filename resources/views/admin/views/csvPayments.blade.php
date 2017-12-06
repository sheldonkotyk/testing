@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
	<h1>Import {!! $import_type !!} to {!! $program->name !!}</h1>
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<div class="app-body">
    <!-- app content here -->
    <div class="col-md-8">

	{!! Form::open(array('url' => 'admin/csv_process_payments/'.$program_id.'')) !!}
	
		
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
				<h3 class="panel-title">Match Donor Fields</h3>
            </div>
            <div class="panel-body">

				<div class="form-group">
					<label>This is the donor field to match...</label>
					<select class="form-control" name="donor_field">
						@foreach ($donor_fields as $df)
							<option value="{!! $df->field_key !!}">{!! $df->field_label !!}</option>
						@endforeach
					</select>
				</div>
				
				<div class="form-group">
					<label>to this column in the uploaded CSV</label>
					<select class="form-control" name="csv_donor_field">
						<option value="_not_selected">Select One</option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
				</div>
				
            </div>
		</div>
		
		<div id="panel-bsbutton" class="panel panel-default magic-element">
            <div class="panel-heading">
				<h3 class="panel-title">Match Recipient Fields</h3>
            </div>
            <div class="panel-body">
            
				<div class="form-group">
					<label>This is the recipient field to match...</label>
					<select class="form-control" name="entity_field">
						@foreach ($entity_fields as $ef)
							<option value="{!! $ef->field_key !!}">{!! $ef->field_label !!}</option>
						@endforeach
					</select>
				</div>
				
				<div class="form-group">
					<label>to this column in the uploaded CSV</label>
					<select class="form-control" name="csv_entity_field">
						<option value="_not_selected">Select One</option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
				</div>
				
            </div>
		</div>
		
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
				<h3 class="panel-title">Payment Details</h3>
            </div>
            <div class="panel-body">
		
				<div class="form-group">
					<label>Payment Amount Column (from the CSV)</label>
					<select class="form-control" name="csv_amount_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
				</div>
				
				<div class="form-group">
					<label>Payment Date (from the CSV)</label>
					<select class="form-control" name="csv_date_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
					<p>Date MUST be in the format YYYY-MM-DD. Dates in other formats may not be imported correctly.</p>
				</div>
				
				<div class="form-group">
					<label>Result or Notes Column (from the CSV)</label>
					<select class="form-control" name="csv_result_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
				</div>
				
				<div class="form-group">
					<label>Payment Method (from the CSV)</label>
					<select class="form-control" name="csv_method_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
					<p>Options are: credit card, credit, card, check, cash, wire. Anything else will be discarded.</p>
				</div>
				
				<div class="form-group">
					<label>Payment Method Default</label>

					<div class="radio">
					  <label>
					    <input type="radio" name="method_default" id="methodDefault1" value="1">
					    Cash
					  </label>
					</div>
					<div class="radio">
					  <label>
					    <input type="radio" name="method_default" id="methodDefault2" value="2">
					    Check
					  </label>
					</div>
					<div class="radio">
					  <label>
					    <input type="radio" name="method_default" id="methodDefault3" value="3">
					    Credit Card
					  </label>
					</div>	
					<div class="radio">
					  <label>
					    <input type="radio" name="method_default" id="methodDefault4" value="4">
					    Wire Transfer
					  </label>
					</div>	
					<p>Select a default payment method to be entered if you don't have a payment method column in your CSV or if you have some payments without a payment method listed.</p>
				</div>
				
				<div class="form-group">
					<label>Payment Type (from the CSV)</label>
					<select class="form-control" name="csv_type_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
					<p>Options are: one time donation, one time, one, sponsorship, recurring. Anything else will be discarded.</p>
				</div>
				
				<div class="form-group">
					<label>Payment Type Default</label>

					<div class="radio">
					  <label>
					    <input type="radio" name="type_default" id="typeDefault1" value="1">
					    Sponsorship
					  </label>
					</div>
					<div class="radio">
					  <label>
					    <input type="radio" name="type_default" id="typeDefault2" value="2">
					    One Time Donation
					  </label>
					</div>
					<div class="radio">
					  <label>
					    <input type="radio" name="type_default" id="typeDefault3" value="3">
					    Recurring Donation
					  </label>
					</div>	
					<p>Select a default payment type to be entered if you don't have a payment type column in your CSV or if you have some payments without a payment type listed.</p>
				</div>
								
            </div>
		</div>
					
		{!! Form::hidden('filename', $filename) !!}
		{!! Form::hidden('import_type', $import_type) !!}
		{!! Form::submit('Insert', array('class' => 'btn btn-primary')) !!}
		<a href="{!! URL::to('admin/manage_program') !!}" class="btn btn-default">Cancel</a>
	{!! Form::close() !!}
	
    </div>
</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
	});
	</script>
@stop