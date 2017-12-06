@extends('frontend.defaultDesignations')

@section('headerscripts')
    <!-- {!! HTML::style('css/demo_table.css') !!} -->
    {!! HTML::style('css/jquery-ui.min.css') !!}
    {!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!} 
    {!! HTML::script('js/jquery.validate.min.js') !!}
    {!! HTML::style('css/bootstrap-select.css') !!}
    {!! HTML::script('js/bootstrap-select.js') !!} 
@stop


@section('content')

<?php $button_name='Complete Donation';
?>

 <?php
        $help_text='';
        $CChelp_text='';
        $NoCChelp_text='';
        if(count($saved_designations))
        {

                if(strpos($frequency_text,'Monthly') == false&&strpos($frequency_text,'Quarterly') == false&&strpos($frequency_text,'Semiannually') == false&&strpos($frequency_text,'Annually') == false)
                {
                    $CChelp_text='<small>Click "Complete Donation" to charge your card <strong>'. $vars['symbol'].number_format($total,2,'.','').'</strong>  </small>';
                    $NoCChelp_text='<small>Click "Complete Donation" to make '.count($entities).' donations '.(count($entities)>1? 's':'').'; '.$frequency_text.'</small>';
                }
                else
                {
                    $CChelp_text='<small>Click "sponsor" to charge your card <strong>'. $vars['symbol'].number_format($total,2,'.','').'</strong> and signup for '.count($saved_designations).' sponsorship'.(count($saved_designations)>1? 's':'').'; '.$frequency_text.'.</small>';
                    $NoCChelp_text='<small>Click "sponsor" to sign up for '.count($saved_designations).' sponsorship'.(count($saved_designations)>1? 's':'').'; '.$frequency_text.'</small>';
                }
            if($useCC)
                $help_text=$CChelp_text;
            else
                $help_text=$NoCChelp_text;
        }

        ?>


@if(!empty($saved_designations))

<div class="panel panel-default" >
  <div class="panel-heading"><h3 class="panel-title">My Order
  <span class="badge pull-right">1</span></h3></div>
  <div class="table-responsive">
    <table class="table">
    <thead>
        <tr>
        <th> </th>
        <th>Name</th>
        <th><div class="pull-right">Paid How Often</div></th>
        <th><div class="pull-left">How Much</div></th>
        <th> </th>
        </tr>
    </thead>
        <tbody>
         
        @foreach($saved_designations  as $id => $amount)

        <tr>

        <td>

        </td>
        <td>
            <h5>{!!$designations[$id]->name!!}</h5>
        </td>

          <td>
           <div class="form-group pull-right">
                <!-- {!! Form::label('designation_frequency'.$id, 'Frequency') !!} -->
                <select  name="d_frequency{!!$id!!}" onchange="if (this.value) window.location.href=this.value; this.disabled=true;" class='form-control selectpicker'>
                    @foreach ($d_frequency_options as $num=> $f_text)
                        <option {!!$num == $d_frequencies[$id] ? "selected" : ""!!} value="/frontend/checkout_update_designation_frequency_only/{!!$client_id!!}/{!!$hysform_id!!}/{!!$designation_id!!}/{!!$id!!}/{!!$num!!}/{!!$session_id!!}">{!! $f_text !!}</option>
                    @endforeach
                </select>
            </div>
        
        </td>

        <td>
             <div class="form-group pull-left">
                    {!!Form::text('sp_amount'.$id,$vars['symbol'].$amount,$attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled'))!!}
                    </div>
        </td>
      
        <td>

       
            <div class="pull-right input-groups">
            {!!Form::open(array('url' => URL::to('frontend/checkout_remove_designation_only/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$id.'/'.$vars['symbol'].'/'.$session_id)))!!}
            {!!Form::button('<span class="glyphicon glyphicon-remove"></span> Remove', array('class' => 'btn btn-danger','onclick'=> 'this.disabled=true;this.form.submit();'))!!}
            {!!Form::close()!!}
            </div>
        
        </td>
        </tr>
      @endforeach
        
        <tr>
           <td></td>
           <td></td>
            <td>
            <div class='pull-right'><strong>Total Donation:</strong></div>
            </td>
            <td>
            <div class="pull-left">{!!$vars['symbol']!!}{{$total}}</div>
            </td>
            <td>
            </td>
            </tr>
        

        


      </tbody>
    </table>
    </div>
</div>
    @endif


 @if (isset($designation)&&$designations_allowed=='1'&&empty($saved_designations))
<div class="panel panel-default" >
      
    <div class="panel-heading"><h3 class="panel-title">
        {!!$designation->name!!}
        <span class="pull-right glyphicon glyphicon-gift"></span></h3> </div>

        <div class="panel-body">
              {!!Form::open( array( 'url' => 'frontend/checkout_add_designation_only/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$vars['symbol'].'/'.$session_id,'id'=>'addamount'))!!}
                <div class="form-inline pull-left ">
                 <div class='form-group'>
             
                   
                    <select  name="designation" class='form-control hidden' >
                            <option value="{!!$designation->id!!}">{!! $designation->name !!}</option>
                    </select>

                    </div>
                    </div>
                        {!! Form::label('designation_amount', 'Donation Amount') !!}
                    @if(empty($designation->donation_amounts))
                        {!!Form::text('designation_amount','',array('placeholder' => 'Enter amount','class' =>'form-control','required'=>""))!!}
                    @else
                        {!!Form::select('designation_amount',$donation_amounts_array,null,array('class' =>'form-control','required'=>""))!!}
                    @endif
                   <br/>
                   <p class="help-text">{!!$designation->info!!}</p>
                   <br/>
                   {!! Form::submit('Add Donation', array('class' => 'btn btn-primary form-control')) !!}
                    {!! $errors->first('designation_amount', '<p class="text-danger">:message</p>') !!}

                    {!!Form::close()!!}

          </div>
    </div>
      @endif

@if(!empty($saved_designations))

    @if($session_logged_in=='true')

     <div class="pull-left well" style="width:45%">


        {!! Form::open(array('url'=>'frontend/checkout_login_only/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id)) !!}
            <legend>You are logged in <strong>{!!$session_donor_name!!}</strong></legend>
            <p class="help-block">Fill out the form below to complete your order</p>

         

                <div class="well">

                <legend>Credit Card Payment Info</legend>
                <input class="form-control" id='method_login' type="hidden" name="method_login" value="3">
                <input class="form-control" id='page' type="hidden" name="page" value="cc">
                <div class="form-group">
                {!!Form::label('firstName','First Name')!!} <span class="label label-primary required">Required</span>
                {!!Form::text('firstName','',$attributes=array('class'=>'form-control','placeholder' =>'First Name','value required' =>""))!!}
                </div>
                <div class="form-group">
                {!!Form::label('lastName','Last Name')!!} <span class="label label-primary required">Required</span>
                {!!Form::text('lastName','',$attributes=array('class'=>'form-control','placeholder' =>'Last Name','value required' =>""))!!}
                </div>
                <div class="form-group">
                {!!Form::label('number','Credit Card Number')!!} <span class="label label-primary required">Required</span>
                {!!Form::text('number',null,$attributes=array('class'=>'form-control','placeholder' =>'Enter Credit Card Number','value required' =>""))!!}
                </div>
                <div class="form-group">
                {!!Form::label('cvv','Card Security Code')!!} <span class="label label-primary required">Required</span>
                {!!Form::text('cvv','',$attributes=array('class'=>'form-control','placeholder' =>'CVV','value required' =>""))!!}
                </div>
                <div class="form-group">
                {!!Form::label('expiryMonth','Expiration Month')!!} <span class="label label-primary required">Required</span>
                {!!Form::text('expiryMonth','',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                </div>
                <div class="form-group">
                {!!Form::label('expiryYear','Expiration Year')!!} <span class="label label-primary required">Required</span>
                {!!Form::text('expiryYear','',$attributes=array('class'=>'form-control','placeholder' =>'YYYY','value required' =>""))!!}
                </div>
                </div>
            
            <div id="cc-form_login" class="form-group"></div>

          
                {!! Form::submit('Submit', array('class' => 'btn btn-primary')) !!}
        {!! Form::close() !!}
        
        </div>


    @endif


    @if($session_logged_in=='false')
        @if($login_box=='1')
        <div class="col-md-6 col-md-push-6">
        <div class="panel panel-default ">
             <div class="panel-heading"><h3 class="panel-title">Already have and account?  <span class="pull-right glyphicon glyphicon-log-in"></span></h3></div>

             <div class="panel-body">
        {!! Form::open(array('url'=>'frontend/checkout_login_only/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id,'id'=>'login')) !!}
            <p class="help-block">If you already have a donor account, you can sign in below to make {!!($num_of_sponsorships > 1 ? 'these donations' : 'this donation')!!} to your account.</p>

            <div class="form-group">
                {!! Form::label('login_username', 'Username') !!}
                    {!! Form::text('login_username', '', array('placeholder' => 'Enter your Username', 'class' => 'form-control' , 'required'=>"")) !!}
            </div>
                    {!! $errors->first('login_username', '<p class="text-danger">:message</p>') !!}
            

            <div class="form-group">
                {!! Form::label('login_password', 'Password') !!}
                <div class="input-group">
                    {!! Form::password('login_password', $attributes = array('placeholder' => 'Enter your password', 'class' => 'form-control' , 'required'=>"")) !!}
                     
                    <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
                </div>
                {!! $errors->first('login_password', '<p class="text-danger">:message</p>') !!}
                
            </div>   
            <div class='form-inline'>
            
                 @if ($checks=='1')
                    <div class="form-group">
                        {!! Form::label('method_login', 'Method') !!}
                    </div>
                    @if ( $useStripe == true)
                        <div class="form-group">
                        {!! Form::select('method_login', array('3' => 'Credit Card','2' => 'Check'), null, array('class' => 'form-control')) !!}
                        </div>
                    @endif
                     @if ( $useStripe == false)
                        <div class="form-group">
                        {!! Form::select('method_login', array('2' => 'Check'), null, array('class' => 'form-control')) !!}
                        </div>
                    @endif
                @endif
                @if ($checks=='')
                    @if ( $useStripe == true)
                        <div class="form-group">
                        {!! Form::select('method_login', array('3' => 'Credit Card'), null, array('class' => 'form-control hidden')) !!}
                        </div>
                    @endif
                     @if ( $useStripe == false)
                        <div class="form-group">
                        Error: "Admin must enable payment options in program settings."
                        </div>
                    @endif

                @endif    
            
            <div id="cc-form_login" class="form-group"> </div>
            </div>

            <div class='form-group'>
                {!! Form::submit('Login and Complete Donation', array('class' => 'btn btn-primary form-control' )) !!}
                </div>
        {!! Form::close() !!}
        </div>
        </div>
        </div>
    @endif
        <div class="col-md-6 col-md-pull-6">
        <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title">New Donor Sign-up  <span class="pull-right glyphicon glyphicon-list-alt"></span></h3></div>

        <div class="panel-body">
        @if($login_box=='')
        <p class="help-block">Please fill out the form below to set up your donation{!!($num_of_sponsorships > 1 ? 's' : '')!!}.</p>
        @endif
        @if($login_box=='1')
        <p class="help-block">If you are a new donor, please fill out the form below to set up your donation{!!($num_of_sponsorships > 1 ? 's' : '')!!}.</p>
        @endif
        {!! Form::open(array('url'=>'frontend/checkout_signup_only/'.$client_id.'/'.$hysform_id.'/'.$designation_id.'/'.$session_id,'id'=>'signup')) !!}

            @foreach ($signup_fields as $field)
                <?php $field_type= $field->field_type?>
                {!! Form::$field_type($field) !!}
            @endforeach
            
            
<input style="display:none" type="text" name="fakeusernameremembered"/>
        <input style="display:none" type="password" name="fakepasswordremembered"/>

            <div class="row">
                <div class="col-md-6 form-group">
                    {!! Form::label('signup_username', 'Username') !!}
                    <span class="label label-primary required pull-right">Required</span>
                    {!! Form::text('signup_username', '', $attributes = array('placeholder' => 'Your username', 'class' => 'form-control', 'required'=>"")) !!}
                    {!! $errors->first('signup_username', '<p class="text-danger">:message</p>') !!}
                </div>
                <div class="col-md-6 form-group">
                     {!! Form::label('email', 'Email Address') !!}
                    <span class="label label-primary required pull-right">Required</span>
                    {!! Form::email('email', '', $attributes = array('placeholder' => 'Your email address', 'class' => 'form-control', 'required'=>"",'email'=>"")) !!}
                    {!! $errors->first('email', '<p class="text-danger">:message</p>') !!}
                </div>
            </div>
        
            <div class="row">
                <div class="col-md-6 form-group">
                    {!! Form::label('signup_password', 'Password') !!}
                    <span class="label label-primary required pull-right">Required</span>
                    {!! Form::password('signup_password', $attributes = array('placeholder'=>'Your password','class' => 'form-control', 'required'=>"")) !!}
                </div>
            </div>

            <div id="cc-form_signup">
       @if ( $useCC == true )

            <div class="panel panel-primary">
                <div class="panel-heading"><h3 class="panel-title pull-left"><span class="glyphicon glyphicon-credit-card"></span> Your Credit Card</h3> 
                @if($useCC=='stripe')
                    <a class="pull-right" href="https://stripe.com" target="_blank"><img class="" src="{!!URL::to('img/solid.png')!!}"></a> 
                @endif
                <div class="clearfix"></div>
                </div>
                <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                {!!Form::label('firstName','First Name')!!} 
                                {!!Form::text('firstName','',$attributes=array('class'=>'form-control','placeholder' =>'First Name','value required' =>""))!!}
                            </div>
                             <div class="col-md-6 form-group">
                                {!!Form::label('lastName','Last Name')!!} 
                                {!!Form::text('lastName','',$attributes=array('class'=>'form-control','placeholder' =>'Last Name','value required' =>""))!!}
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-7 form-group">
                                {!!Form::label('number','Credit Card Number')!!}
                                {!!Form::text('number','',$attributes=array('class'=>'form-control','placeholder' =>'Credit Card Number','value required' =>""))!!}
                            </div>
                            <div class="col-md-5 form-group">
                                {!!Form::label('cvv','Security Code')!!}
                                {!!Form::text('cvv','',$attributes=array('class'=>'form-control','placeholder' =>'Security Code','value required' =>""))!!}
                            </div>
                        </div>
                    <div class="form-inline">
                            <div class="col-md-4 form-group">
                            {!!Form::label('expiry','Expiration Date: ',array('class'=>''))!!}
                            </div>
                            <div class="col-md-4 form-group">
                            {!!Form::select('expiryMonth',$months,'',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                            </div>
                            <div class="col-md-4 form-group">
                            {!!Form::select('expiryYear',$years,'',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                            </div>
                        </div>
                </div>
            </div>
        @endif
        </div>
               <div class='form-inline'>
                
                @if ($checks=='1')
                    <div class="form-group">
                       {!! Form::label('method_signup', 'Method') !!}
                    </div>
                    @if ( $useStripe == true)
                        <div class="form-group">
                        {!! Form::select('method_signup', array('3' => 'Credit Card','2' => 'Check'), null, array('class' => 'form-control')) !!}
                        </div>
                    @endif
                     @if ( $useStripe == false)
                        <div class="form-group">
                        {!! Form::select('method_signup', array('2' => 'Check'), null, array('class' => 'form-control')) !!}
                        </div>
                    @endif
                @endif
                @if ($checks=='')
                    @if ( $useStripe == true)
                        <div class="form-group">
                        {!! Form::select('method_signup', array('3' => 'Credit Card'), null, array('class' => 'form-control hidden')) !!}
                        </div>
                    @endif
                     @if ( $useStripe == false)
                        <div class="form-group">
                        Error: "Admin must enable payment options in program settings."
                        </div>
                    @endif

                @endif    
              </div>
                
                <div class='alert alert-info' id="cc-help_text">
            {!!$help_text!!}
            </div>  

                <div class='form-group'>
            {!! Form::submit($button_name, array('class' => 'btn btn-primary form-control')) !!}
            </div>
        {!! Form ::close() !!}
    </div>
    </div>
    </div>
    @endif

@endif

@stop
@section('footerscripts')
<script>
$(document).ready(function() {

     var CCForm= '<div class="well">'+
                        '<legend><span class="glyphicon glyphicon-credit-card"></span> Credit Card Payment Info</legend>'+
                        '<div class="form-group">'+
                            '<div class="row">'+
                                '<div class="col-md-6">'+
                                    '{!!Form::label("firstName","First Name")!!} '+
                                    '{!!Form::text("firstName","",$attributes=array("class"=>"form-control","placeholder" =>"First Name","value required" =>""))!!}'+
                                '</div>'+
                                 '<div class="col-md-6">'+
                                   '{!!Form::label("lastName","Last Name")!!} '+
                                    '{!!Form::text("lastName","",$attributes=array("class"=>"form-control","placeholder" =>"Last Name","value required" =>""))!!}'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                        '<div class="form-group">'+
                            '<div class="row">'+
                                '<div class="col-md-7">'+
                                    '{!!Form::label("number","Credit Card Number")!!}'+
                                    '{!!Form::text("number","",$attributes=array("class"=>"form-control","placeholder" =>"Credit Card Number","value required" =>""))!!}'+
                                '</div>'+
                                '<div class="col-md-5">'+
                                    '{!!Form::label("cvv","Security Code")!!}'+
                                    '{!!Form::text("cvv","",$attributes=array("class"=>"form-control","placeholder" =>"Security Code","value required" =>""))!!}'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                        '<div class="form-group">'+
                            '<div class="row">'+
                                '<div class="col-md-4">'+
                                '{!!Form::label("expiry","Expires",array("class"=>"pull-right"))!!}'+
                                '</div>'+
                                '<div class="col-md-4">'+
                                '{!!Form::label("expiryMonth","Month")!!}'+
                                '{!!Form::select("expiryMonth",$months,"",$attributes=array("class"=>"form-control","placeholder" =>"MM","value required" =>""))!!}'+
                                '</div>'+
                                '<div class="col-md-4">'+
                                '{!!Form::label("expiryYear","Year")!!}'+
                                '{!!Form::select("expiryYear",$years,"",$attributes=array("class"=>"form-control","placeholder" =>"MM","value required" =>""))!!}'+
                                '</div>'+
                            '</div>'+
                        '</div>'+
                    '</div>';
      
        //$().validate();
        $("#addamount").validate({
                submitHandler: function(form){
                    if(!this.wasSent){
                        this.wasSent = true;
                        $(':submit', form).val('Adding your Gift...')
                                          .attr('disabled', 'disabled')
                                          .addClass('disabled');
                        form.submit();
                    } else {
                        return false;
                    }
                }
            });

        $("#signup").validate({
                submitHandler: function(form){
                    if(!this.wasSent){
                        this.wasSent = true;
                        $(':submit', form).val('Signing you up...')
                                          .attr('disabled', 'disabled')
                                          .addClass('disabled');
                        form.submit();
                    } else {
                        return false;
                    }
                }
            });
        $("#login").validate({
                submitHandler: function(form){
                    if(!this.wasSent){
                        this.wasSent = true;
                        $(':submit', form).val('Logging you in...')
                                          .attr('disabled', 'disabled')
                                          .addClass('disabled');
                        form.submit();
                    } else {
                        return false;
                    }
                }
            });

        $('#method_signup').change(function() {
            var selected = $(this).val();
            var useCC = '{!! $useCC !!}';
            
            if ( selected == 3 ) {
                if ( useCC != false )
                {
                        $('div#cc-form_signup').html(CCForm);
                        $('div#cc-help_text').html('{!!$CChelp_text!!}');
                }
            } else {
                $('div#cc-form_signup').html('');
                $('div#cc-help_text').html('{!!$NoCChelp_text!!}');
            }

        });
      
   
   
    
});


</script>
       
@stop