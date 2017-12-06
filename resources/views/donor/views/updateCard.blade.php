@extends('frontend.default')

@section('content')



        <div class="pull-left panel panel-default">

        <div class="panel-heading"><h3 class="panel-title"> <span class="glyphicon glyphicon-credit-card"></span>  {!!($credit_card=='[None]' ? 'Add' : 'Update' )!!} Your Credit Card </h3></div>
        <div class="panel-body">
    {!! Form::open(array('url'=>'frontend/donor_update_card/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}
        
             <p class="help-block">Fill out the form below to {!!($credit_card=='[None]' ? 'add' : 'update' )!!} your Credit Card.</p>
                <!-- {!!Form::label('firstName','First and Last Name')!!} <span class="label label-primary required pull-right">Required</span> -->
                <div class="row">
                    <div class="col-md-6 form-group">
                        <!-- {!!Form::label('firstName','First Name')!!}  -->
                        {!!Form::text('firstName','',$attributes=array('class'=>'form-control','placeholder' =>'First Name','value required' =>""))!!}
                    </div>
                     <div class="col-md-6 form-group">
                        <!-- {!!Form::label('firstName','Last Name')!!}  -->
                        {!!Form::text('lastName','',$attributes=array('class'=>'form-control','placeholder' =>'Last Name','value required' =>""))!!}
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-7 form-group">
                        <!-- {!!Form::label('number','Credit Card Number')!!} -->
                        {!!Form::text('number','',$attributes=array('class'=>'form-control','placeholder' =>'Credit Card Number','value required' =>""))!!}
                    </div>
                    <div class="col-md-5">
                        <!-- {!!Form::label('cvv','Security Code')!!} -->
                        {!!Form::text('cvv','',$attributes=array('class'=>'form-control','placeholder' =>'Security Code','value required' =>""))!!}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                    {!!Form::label('expiry','Expires',array('class'=>'pull-right'))!!}
                    </div>
                    <div class="col-md-4 form-group">
                    <!-- {!!Form::label('expiryMonth','Expiration Month')!!} -->
                    {!!Form::select('expiryMonth',$months,'',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                    </div>
                    <div class="col-md-4 form-group">
                    <!-- {!!Form::label('expiryYear','Expiration Year')!!} -->

                    {!!Form::select('expiryYear',$years,'',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                    </div>
                </div>
    
        
        <div id="cc-form_login"></div>

    <p class='info-block'>@if(!empty($sponsorships))
     <strong>The following sponsorship{!!(count($sponsorships)>1 ? 's': '')!!} will be paid with your {!!($credit_card=='[None]' ? 'new' : 'updated' )!!} credit card.</strong><br>
     @foreach($sponsorships as $sponsorship)
        @if($sponsorship['frequency']=='Monthly')
            {!!$sponsorship['name']!!} @ {!!$sponsorship['currency_symbol']!!}{{$sponsorship['commit']}} {!!$sponsorship['frequency']!!}
        @else
            {!!$sponsorship['name']!!} @ {!!$sponsorship['currency_symbol']!!}{{$sponsorship['commit']}} per Month <em>(paid {!!$sponsorship['frequency']!!})</em>
        @endif
     <br>
     @endforeach
    @endif</p>

      
            {!! Form::submit(($credit_card=='[None]' ? 'Add' : 'Update' ).' Credit Card', array('class' => 'btn btn-primary form-control')) !!}
    {!! Form::close() !!}
        </div>
    </div>


@stop