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

	{!! Form::open(array('url' => 'admin/csv_process_relationships/'.$program_id.'')) !!}
	
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
		
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
				<h3 class="panel-title">Sponsorship Details</h3>
            </div>
            <div class="panel-body">
		
				<div class="form-group">
					<label>Commitment amount column (from the CSV)</label>
					<select class="form-control" name="csv_commitment_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
				</div>
				
				<div class="form-group">
					<label>Sponsorship start date column</label>
					<select class="form-control" name="csv_date_field">
						<option value="_not_selected"></option>
						@foreach ($first_row as $row)
							@foreach ($row as $k => $v)
								<option value="{!! $k !!}">{!! $v !!}</option>
							@endforeach
						@endforeach
					</select>
					<p>Date must be in the format of YYYY-MM-DD. If no date is entered the software will default to today's date and will assume the next payment due date is one month from today.</p>
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