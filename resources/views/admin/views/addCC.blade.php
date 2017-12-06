@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

  	@if(isset($client))
    	@if (empty($client->stripe_cust_id))
			<h2>You do not have an active credit card on file</h2>
			<p>Please add a valid credit card in order to continue using HelpYouSponsor.</p>
		@endif
    @endif

    <h1>
	@if(!empty($profileThumb))
		<img src="{!! $profileThumb !!}" class="img-rounded" width="50px" />
	@endif
	
	@if(isset($name))
		{!! $name['name'] !!} 
	@endif

	<small><span class="glyphicon glyphicon-credit-card"></span> <em>Enter Credit Card Information</em></small></h1>

	@if(isset($name))
    	@include('admin.views.donorMenu')
    @endif    

  
        <hr>




		<div class="col-md-8">

			<div id="panel-bsbutton" class="panel panel-default magic-element">
			        <div class="panel-heading">
			            <div class="panel-icon"><i class="glyphicon glyphicon-credit-card"></i></div>
			           	<div class="panel-actions">
			                		<div class="label label-success">New Credit Card</div>
			            </div>
			            <h3 class="panel-title">Add Credit Card</h3>
			        </div><!-- /panel-heading -->
			        <div class="panel-body">
		
						{!! Form::open() !!}
							<div class="form-group">
								<label for="firstName">First Name</label>
								<input class="form-control" type="text" name="firstName" placeholder="First Name">
								{!! $errors->first('firstName', '<p class="text-danger">:message</p>') !!}
							</div>
							<div class="form-group">
								<label for="lastName">Last Name</label>
								<input class="form-control" type="text" name="lastName" placeholder="Last Name">
								{!! $errors->first('lastName', '<p class="text-danger">:message</p>') !!}
							</div>
							<div class="form-group">
								<label for="number">Credit Card Number</label>
								<input class="form-control" type="text" name="number" placeholder="Enter Credit Card Number">
								{!! $errors->first('number', '<p class="text-danger">:message</p>') !!}
							</div>
							
							<div class="form-group">
								<div class="row">
									<div class="col-xs-2">
										<label for="expiryMonth">Month</label>
										<input class="form-control" type="text" name="expiryMonth" placeholder="MM">
										{!! $errors->first('expiryMonth', '<p class="text-danger">:message</p>') !!}
									</div>
									<div class="col-xs-2">
										<label for="expiryYear">Year</label>
										<input class="form-control" type="text" name="expiryYear" placeholder="YYYY">
										{!! $errors->first('expiryYear', '<p class="text-danger">:message</p>') !!}
									</div>
									<div class="col-xs-2">
										<label for="cvv">CVV</label>
										<input class="form-control" type="text" name="cvv" placeholder="CVV">
										{!! $errors->first('cvc', '<p class="text-danger">:message</p>') !!}
									</div>
								</div>
							</div>
							
							<div class="form-group">
							{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
							<a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
							</div>
						{!! Form::close() !!}
						</div>
					</div>
				</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
	});
	</script>
@stop