@extends('admin.default')

@section('headerscripts')
	{!! HTML::script('js/jquery.validate.min.js') !!}
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
	{!! HTML::style('css/jquery-ui.min.css') !!}
	{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}	

				@stop

@section('content')
<div id="fb-root"></div>

<h1>
	<small><a href="{!!URL::to('admin/show_all_entities',array($program->id))!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!! $program->name !!} </a></small>
	
	<div class="btn-group">
		<a href="{!! URL::to('admin/add_entity', array($program->id)) !!}">
		    <button type="button" class="btn btn-primary">
		        <span class="glyphicon glyphicon-plus"></span> Add Recipient
		    </button>
	    </a>
	</div>

	<div class="pull-right">
            <small>Share:</small>
                <a class="btn btn-xs btn-default btn-extend be-left" href="https://twitter.com/share?url={!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}&text=Meet {!!$name!!}:" target="_blank">
                        <i class="icon ion-social-twitter"></i>Tweet</a> 
                 <a class="btn btn-xs btn-default btn-extend be-left" href="https://www.facebook.com/sharer/sharer.php?u={!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}&display=popup" target="_blank">
                        <i class="icon ion-social-facebook"></i>Share</a> 
                 <a class="btn btn-xs btn-default btn-extend be-left" href="mailto:?subject=Meet%20{!!$name!!}&body=Click%20on%20the%20link%20to%20find%20out%20about%20{!!$name!!}%0D%0A{!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}">
                         Email
                          <i class="glyphicon glyphicon-envelope"></i>
                         </a>
                    <a class="btn btn-xs btn-default btn-extend be-left" data-toggle="collapse" href="#collapseTwo">
                        <i class="glyphicon glyphicon-link"></i> Embed</a>
            </div>
</h1>

<h1>
	@if(!empty($profileThumb))
	<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	{!! $name !!} <small><span class="glyphicon glyphicon-pencil"></span> <em>Edit Profile</em>@if($entity['deleted_at']!=null) - (<span class="glyphicon glyphicon-trash"></span> <em>Archived</em>)@endif</small>	

	 
 </h1>

	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif

	@include('admin.views.entityMenu')
	<div class="app-body">
	    <!-- app content here -->                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
	        <div class="panel-heading">
	            <div class="panel-icon"><i class="glyphicon glyphicon-pencil"></i></div>
	           	<div class="panel-actions">
	           		@foreach($details as $k => $d)
	                	<div class="label label-success">{!!$k!!} {!!$d!!}</div>
	                @endforeach
	            </div>
	            <h3 class="panel-title">Edit {!!$name!!}'s Profile</h3>
	        </div><!-- /panel-heading -->
	        
	        <div class="panel-body">
			
				<div class="col-md-8">
					@if (!empty($sponsors))
					<dl class="dl-horizontal">
					<dt>Current Sponsors</dt>
						@foreach ($sponsors as $sponsor)
							@if ($d = 'donor-'.$sponsor['hysform_id'].'')
							@endif
							@if (isset($permissions->$d) && $permissions->$d == 1)
								<dd><p><a href="{!! URL::to('admin/edit_donor') !!}/{!! $sponsor['id'] !!}">{!! $sponsor['name'] !!}</a>
								{!!$settings->currency_symbol!!}{{$sponsor['amount']}} paid {!!$sponsor['frequency']!!}
								 <a class="btn btn-default btn-xs" href="{!! URL::to('admin/send_email', array($profile['program_id'], 'profile_update', 'donor', $sponsor['id'], $profile['id'])) !!}">Send Update Email</a>
								 <a class="btn btn-default btn-xs" href="{!!URL::to('admin/send_message',array($profile['id'],$sponsor['id'],'entity',null))!!}">Compose Message</a>
								 </p>
								</dd>
							@elseif($sponsor['name']=='No Name Found')
								<dd>{!! $sponsor['name'] !!} (donor Deleted)</dd>
							@else
								<dd>{!! $sponsor['name'] !!}</dd>
							@endif
						@endforeach
					</dl>
					<hr>
					@endif
					@if (!empty($donations)&&isset($permissions->donations))
					<dl class="dl-horizontal">
					<dt>Donations</dt>
						@foreach ($donations as $donation)
							@if ($d = 'donor-'.$donation['hysform_id'].'')
							@endif
							@if (isset($permissions->$d) && $permissions->$d == 1)
							<dd><p><a href="{!! URL::to('admin/edit_donor') !!}/{!! $donation['id'] !!}">{!! $donation['name'] !!}</a>
							{!!$settings->currency_symbol!!}{{$donation['amount']}} 
							 <a class="btn btn-default btn-xs" href="{!! URL::to('admin/send_email', array($profile['program_id'], 'profile_update', 'donor', $donation['id'], $profile['id'])) !!}">Send Update Email</a></p>
							</dd>
							@else
							<dd>{!! $donation['name'] !!}</dd>
							@endif
						@endforeach
					</dl>
					<hr>
					@endif
			
					{!! Form::open() !!}
					@foreach ($fields as $field)
						<?php $field_type = $field->field_type ?>
						@if (isset($profile[$field->field_key]))
							{!! Form::$field_type($field, $profile[$field->field_key]) !!}
						@else
							{!! Form::$field_type($field) !!}
						@endif
					@endforeach
					
					@if($programData['type'] == 'contribution')
						<div class="form-group">
							{!! Form::label('sp_num', 'Contribution Level Required') !!}
			
							@if(empty($programData['sp_num'][0]['amount']))
								{!!Form::text('sp_num',$profile['sp_num'],array('placeholder'=>'Enter the required contribution amount', 'class'=>'form-control'))!!}
							@else
							<select class="form-control" name="sp_num">
									<option value=''>Please select</option>
								@foreach ($programData['sp_num'] as $num) 
									@if ($num['amount'] == $profile['sp_num'])
									<?php $con_exists=1;?>
									<option value="{!! $num['amount'] !!}" selected="selected">{!! $num['symbol'] !!}{{ $num['amount'] }}</option>
									@else
									<option value="{!! $num['amount'] !!}">{!! $num['symbol'] !!}{{ $num['amount'] }}</option>
									@endif
								@endforeach
								@if(!isset($con_exists)&&!empty($profile['sp_num']))
									<option selected="selected" value="{!! $profile['sp_num'] !!}">{!! $num['symbol'] !!}{{ $profile['sp_num'] }}</option>
								@endif
							</select>
							@endif
							{!! $errors->first('sp_num', '<p class="text-danger">:message</p>') !!}
						</div>
					@endif
			
					@if($programData['type'] == 'funding')
						<div class="form-group">
							{!! Form::label('sp_num', 'Funding Level Required') !!}
		
							@if(empty($programData['funded_amounts'][0]))
								{!!Form::text('sp_num',$profile['sp_num'],array('placeholder'=>'Enter amount in '.$programData['currency_symbol'],'class'=>'form-control'))!!}
							@else
							<select class="form-control" name="sp_num">
									<option value=''>Please select</option>
								@foreach ($programData['funded_amounts'] as $num) 
									@if ($num == $profile['sp_num'])
									<option value="{!! $num !!}" selected="selected">{!!$programData['currency_symbol']!!}{{ $num }}</option>
									@else
									<option value="{!! $num !!}">{!!$programData['currency_symbol']!!}{{ $num }}</option>
									@endif
								@endforeach
							</select>
							@endif
							{!! $errors->first('sp_num', '<p class="text-danger">:message</p>') !!}
						</div>
					@endif
					
					@if($programData['type'] == 'number')
						<div class="form-group">
							{!! Form::label('sp_num', 'Number of Sponsors Required') !!}
							<span class="label label-primary required">Required</span>
							@if (count($programData['number_sponsors']) > 0)
							<select class="form-control" required name="sp_num">
								<option value=''>Please select</option>
								@foreach ($programData['number_sponsors'] as $num) 
									@if ($num == $profile['sp_num'])
									<?php $num_exists=1;?>
									<option value="{!! $num !!}" selected="selected">{!! $num !!}</option>
									@else
									<option value="{!! $num !!}">{!! $num !!}</option>
									@endif
								@endforeach
								@if(!isset($num_exists)&&!empty($profile['sp_num']))
									<option selected="selected" value="{!! $profile['sp_num'] !!}">{!! $profile['sp_num'] !!}</option>
								@endif
							</select>
							@else
								@foreach ($programData['number_sponsors'] as $num)
									<br>{!! $profile['sp_num'] !!}
									<input type="hidden" name="sp_num" value="{!! $num !!}">
								@endforeach
							@endif
						</div>
				
						<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							@if (count($programData['sp_amount']) > 0)
							<select class="form-control" name="sp_amount">
								<option>Please select</option>
								@foreach ($programData['sp_amount'] as $sp_amount) 
									@if ($sp_amount['amount'] == $profile['sp_amount'])
									<option value="{!! $sp_amount['amount'] !!}" selected="selected">{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}</option>
									@else
									<option value="{!! $sp_amount['amount'] !!}">{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}</option>
									@endif
								@endforeach
							</select>
							@else
								@foreach ($programData['sp_amount'] as $sp_amount)
									<br>{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}						
									<input type="hidden" name="sp_amount" value="{!! $sp_amount['amount'] !!}">
								@endforeach
							@endif
						</div>
					@endif

					@if($programData['type']=='one_time')
					<div class="form-group">
							{!! Form::label('sp_amount', 'Sponsorship Amount') !!}
							@if (count($programData['sp_amount']) > 0)
							<select class="form-control" name="sp_amount">
								<option>Please select</option>
								@foreach ($programData['sp_amount'] as $sp_amount) 
									@if ($sp_amount['amount'] == $profile['sp_amount'])
									<option value="{!! $sp_amount['amount'] !!}" selected="selected">{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}</option>
									@else
									<option value="{!! $sp_amount['amount'] !!}">{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}</option>
									@endif
								@endforeach
							</select>
							@else
								@foreach ($programData['sp_amount'] as $sp_amount)
									<br>{!! $sp_amount['symbol'] !!}{{ $sp_amount['amount'] }}						
									<input type="hidden" name="sp_amount" value="{!! $sp_amount['amount'] !!}">
								@endforeach
							@endif
						</div>
					@endif


					<div class="col-md-8 form-group">
					{!! Form::submit('Update', array('class' => 'btn btn-primary form-control')) !!}
					</div>
					<div class="col-md-4 form-group">
					<a href="{!! URL::to('admin/show_all_entities') !!}/{!! $profile['program_id'] !!}" class="btn btn-default form-control">Cancel</a>
					</div>
					{!! Form ::close() !!}
				
				</div><!-- col-md-8 -->
				
				<div class="col-md-4">
					<img src="{!! $profilePic !!}" class="img-rounded img-responsive" />
					<p>Profile updated: {!! Carbon::createFromTimeStamp(strtotime($profile['updated_at']))->diffForHumans() !!}</p>
					@if ($profile['wait_time'] != '0000-00-00')
					<p>Wait time: {!! Carbon::createFromTimeStamp(strtotime($profile['wait_time']))->diffInDays() !!} days</p>
					@endif
				</div><!-- col-md-4 -->
				
			</div> <!-- panel-body -->
		</div><!-- panel-bsbutton -->
	</div> <!-- app-body -->
@stop

@section('footerscripts')
<script>
$(document).ready(function() {
	$('.hysTextarea').redactor();
	$("form").validate();	
	$( ".datepicker" ).datepicker({ 
		dateFormat: "yy-mm-dd", 
		changeMonth: true,
		changeYear: true
	});
});
</script>
@stop