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

	{!! Form::open(array('url' => 'admin/csv_process/'.$program_id.'')) !!}
	
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
				<h3 class="panel-title">Match Fields</h3>
            </div>
            <div class="panel-body">

				@foreach ($first_row as $row)
					@foreach ($row as $cell)
					<div class="form-group">
						<label>{!! $cell !!}</label>
						<select class="form-control" name="fields[]">
							<option value="_do_not_import"></option>
							@foreach ($fields as $field)
								@if ($field->field_type != 'hysLink' || $field->field_type != 'hysTable' || $field->field_type != 'hysCheckbox')
								<option value="{!! $field->field_key !!}">{!! $field->field_label !!}</option>
								@endif
							@endforeach
							
							@if ($import_type == 'donors')
								<option value="username">Username</option>
								<option value="email">Email</option>
								<option value="password">Password</option>
								<option value="stripe_cust_id">Stripe Customer ID</option>
								<option value="authorize_profile">Authorize Profile</option>
							@endif
							
						</select>
					</div>
					@endforeach
					<div class="form-group">
						<label>
					    	<input type="checkbox" name="offset" value="1"> Is the first row of your file column headers? Then check this box so it doesn't get imported into your database.
					    </label>
					</div>
					@if ($aii != false)
					<div class="form-group">
						<label>
							<input type="checkbox" name="hysCustomid" value="{!! $aii !!}"> Do you want to automatically add an ID number to each new record?
						</label>
					</div>
					@endif
					
					@if ($import_type == 'donors')
					<div class="form-group">
						<label>
							<input type="checkbox" name="notify_donor" value="1"> Send email to donors with their username and password?
						</label>
					</div>
					@endif
					
					@if ($import_type == 'recipients')
					<h4>Select Defaults</h4>
						@if ($settings['program_type'] == 'contribution')
						<div class="form-group">
							<label>Amount to complete sponsorship</label>
							<select class="form-control" name="sp_num">
									<option value=""></option>
								@foreach ($settings['sp_num'] as $sn)
									<option value="{!! $sn !!}">{!! $sn !!}</option>
								@endforeach
							</select>
						</div>
						@endif
						
						@if ($settings['program_type'] == 'number')
						<div class="form-group">
							<label>Number of Sponsors Needed</label>
							<select class="form-control" name="sp_num">
									<option value=""></option>
								@foreach ($settings['number_spon'] as $ns)
									<option value="{!! $ns !!}">{!! $ns !!}</option>
								@endforeach
							</select>
						</div>
						@endif
						
						@if ($settings['program_type'] == 'funding')
						<div class="form-group">
							<label>Funding Level Needed</label>
							<select class="form-control" name="sp_num">
									<option value=""></option>
								@foreach ($settings['number_spon'] as $ns)
									<option value="{!! $ns !!}">{!! $ns !!}</option>
								@endforeach
							</select>
						</div>
						@endif
						
						<div class="form-group">
							<label>Sponsorship Amount</label>
							<select class="form-control" name="sp_amount">
									<option value=""></option>
								@foreach ($settings['sponsorship_amount'] as $sa)
									<option value="{!! $sa !!}">{!! $sa !!}</option>
								@endforeach
							</select>
						</div>
					@endif
					
				@endforeach
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