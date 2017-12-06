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

				<?php
				if($program=='all')
				{
					$program_id='all';
					$program_name='all';
				}
				else
				{
					$program_id=$program->id;
					$program_name= $program->name;
				}
				?>
						<div class='wrapper list-group'>
						@foreach ($fieldOptions as $k => $v)
							@if ($k == 'program-'.$program_id)
									@if (isset($permissions->$k) && $permissions->$k == 1)
										<div class='left'>
										<div class='list-group-item'>
											<h4 class="list-group-item-heading alert alert-info">My Recipient Fields</h4>
										</div>
										<div class='list-group-item'>
											@foreach ($v as $f)
											<div class="form-group">
												<label>{!! Form::checkbox($f['field_key'], $f['field_label'], $value = null, array('class' => 'program')) !!} {!! $f['field_label'] !!}</label>
											</div> 
											@endforeach
										</div>
										</div>
									@endif
									<div class="right"> 
									<div class="list-group-item">
										<h4 class="list-group-item-heading alert alert-info">System Recipient Fields</h4>
									</div>
									<div class="list-group-item">
										<div class="form-group">
											<label> {!! Form::checkbox('thumb', 1, $value = null, array('class' => 'program')) !!} Profile photo </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('profile_link', 1, $value = null, array('class' => 'program')) !!} Profile photo link </label>
										</div>										
										
										<div class="form-group">
											<label> {!! Form::checkbox('created_at', 1, $value =  null, array('class' => 'program')) !!} Date Added </label>
										</div>
										
										<div class="form-group">
											<label> {!! Form::checkbox('updated_at', 1, $value =  null, array('class' => 'program')) !!} Date Updated </label>
										</div>

										@foreach($details as $name => $detail)
											<div class="form-group">
												<label> {!! Form::checkbox(strtolower(str_replace(' ', '_', $name)), 1, $value =  null, array('class' => 'program')) !!} {!!$name!!} </label>
											</div>
										@endforeach
										
										<div class="form-group">
											<label> {!! Form::checkbox('manage', 1, $value = null, array('class' => 'program')) !!} Manage </label>
										</div>
									</div>
							</div>
							</div>
						@else
							@if ( $type == 'sponsorship')
							<div class='wrapper list-group'>
								<div class='left'>
									<div class='list-group-item'>
										<h4 class="list-group-item-heading alert alert-success">My Donor Fields</h4>
									</div>
									@if (isset($permissions->$k) && $permissions->$k == 1)
										<div class='list-group-item'>
											@foreach ($v as $f)
											<div class="form-group">
												<label>{!! Form::checkbox($f['field_key'], $f['field_label'], $value = null, array('class' => 'donor')) !!} {!! $f['field_label'] !!}</label>
											</div>
											@endforeach
										</div>
											
								</div>
								<div class='right'>
									<div class='list-group-item'>
										<h4 class="list-group-item-heading alert alert-success">System Donor Fields</h4>
									</div>
									<div class='list-group-item'>
										<div class="form-group">
											<label> {!! Form::checkbox('email', 1, $value = null, array('class' => 'donor')) !!} Email Address </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('username', 1, $value = null, array('class' => 'donor')) !!} Username </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('amount', 1, $value = null, array('class' => 'donor')) !!} Sponsorship Amount </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('frequency', 1, $value = null, array('class' => 'donor')) !!} Payment Frequency </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('until', 1, $value = null, array('class' => 'donor')) !!} Sponsorship End Date </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('last', 1, $value = null, array('class' => 'donor')) !!} Date of Last Payment </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('next', 1, $value = null, array('class' => 'donor')) !!} Next Payment Due Date </label>
										</div>
										
										<div class="form-group">
											<label> {!! Form::checkbox('method', 1, $value = null, array('class' => 'donor')) !!} Payment Method </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('donor_created_at', 1, $value =  null, array('class' => 'donor')) !!} Donor Date Added </label>
										</div>
										
										<div class="form-group">
											<label> {!! Form::checkbox('donor_updated_at', 1, $value =  null, array('class' => 'donor')) !!} Donor Date Updated </label>
										</div>

										<div class="form-group">
											<label> {!! Form::checkbox('sponsorship_created_at', 1, $value =  null, array('class' => 'donor')) !!} Sponsorship Date Added </label>
										</div>
									</div>
								</div>
							</div>
									<hr>
								@endif

							@endif
						@endif
						
					@endforeach
						@if($type!='sponsorship')
							<div class="form-group" style="">	
								{!! Form::label('report_name', 'Name this report to save your selections for future use') !!}
								{!! Form::text('report_name', $value = null, $attributes = array('placeholder' => 'Enter a report name', 'class' => 'form-control')) !!}
							</div>
						@endif
					{!! Form::submit('Create', array('class' => 'btn btn-primary', 'id' => 'choose_fields')) !!}
					{!! Form::close() !!}

					@if (! $reports->isEmpty() &&$type!='sponsorship')
					<div class="reports">
						<hr>
						<h4>Select a saved report:</h4>
						@foreach ($reports as $report)
						<a href="{!! URL::to('admin/select_saved_report', array($report->id, $program_id)) !!}">{!! $report->name !!}</a> <a class="pull-right" href="{!! URL::to('admin/remove_saved_report', array($report->id, $program_id)) !!}" title="Delete Report"><span class="glyphicon glyphicon-remove"></span></a><br>
						@endforeach
					</div>
					@endif
			  </div><!-- /panel-body -->
     	   </div><!-- /panel-bsbutton -->
	</div>
</div>
