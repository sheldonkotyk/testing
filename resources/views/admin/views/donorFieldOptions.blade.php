<style>
.wrapper {
 width:600px;
 overflow:hidden;
}

.left {
 width:300px;
 float:left;
}

.right {
 width:300px;
 float:right;
}
</style>


 <!-- app body -->
<div class="app-body">
    <!-- app content here -->
    <div class="magic-layout">
                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-th-list"></i></div>
                <div class="panel-actions">
                    <div class="label label-success">New Report</div>
                </div>
               
                <h3 class="panel-title">Create Report</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

				<p>Check the fields you would like displayed in the table. Your selections will be saved.</p>
				{!! Form::open() !!}
				<div class='wrapper list-group'>
					@foreach ($fieldOptions as $k => $v)
						@if ($k == 'donor-'.$hysform_id.'')
								@if (isset($permissions->$k) && $permissions->$k == 1) 
									<div class='left'>
										<div class='list-group-item'>
											<h4 class="list-group-item-heading alert alert-success">My Donor Fields</h4>
										</div>
										<div class='list-group-item'>
											@foreach ($v as $f)
											<div class="form-group ">
												<label>{!! Form::checkbox($f['field_key'], $f['field_label'], $value = null, array('class' => 'donor')) !!} {!! $f['field_label'] !!}</label>
											</div> 
											@endforeach
										</div>

									</div>
								@endif
								<!-- <div class="form-group">
									<label> {!! Form::checkbox('thumb', 1, $value = null, array('class' => 'program')) !!} Profile photo </label>
								</div> -->

								<div class="right"> 
									<div class="list-group-item">
										<h4 class="list-group-item-heading alert alert-success">System Donor Fields</h4>
									</div>
									<div class="list-group-item">
										<div class="form-group">
											<label> {!! Form::checkbox('email', 1, $value = null, array('class' => 'donor')) !!} Email Address </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('username', 1, $value = null, array('class' => 'donor')) !!} Username </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('created_at', 1, $value =  null, array('class' => 'donor')) !!} Date Added </label>
										</div>
										
										<div class="form-group">
											<label> {!! Form::checkbox('updated_at', 1, $value =  null, array('class' => 'donor')) !!} Date Updated </label>
										</div>

										@foreach($details as $name => $detail)
											<div class="form-group">
												<label> {!! Form::checkbox(strtolower(str_replace(' ', '_', $name)), 1, $value =  null, array('class' => 'donor')) !!} {!!$name!!}</label>
											</div>
										@endforeach
										
										<div class="form-group">
											<label> {!! Form::checkbox('manage', 1, $value = null, array('class' => 'donor')) !!} Manage </label>
										</div>		
									</div>
								</div>
						@endif

					@endforeach
				</div>
					<div class="form-group">	
						{!! Form::label('report_name', 'Name this report to save your selections for future use') !!}
						{!! Form::text('report_name', $value = null, $attributes = array('placeholder' => 'Enter a report name', 'class' => 'form-control')) !!}
					</div>
					
				{!! Form::submit('Update', array('class' => 'btn btn-primary', 'id' => 'choose_fields')) !!}
				{!! Form::close() !!}

				@if ( ! $reports->isEmpty() )
				<div class="reports col-md-4">
					<hr>
					<h4>Select a saved report:</h4>
					@foreach ($reports as $report)
					<a href="{!! URL::to('admin/select_donor_saved_report', array($report->id, $hysform_id)) !!}">{!! $report->name !!}</a> <a class="pull-right" href="{!! URL::to('admin/remove_donor_saved_report', array($report->id, $hysform_id)) !!}" title="Delete Report"><span class="glyphicon glyphicon-remove"></span></a><br>
					@endforeach
				</div>
				@endif
			</div>
		</div>
	</div>
