@extends('frontend.default')

@section('headerscripts')
    {!! HTML::style('css/demo_table.css') !!}
    {!! HTML::style('css/jquery-ui.min.css') !!}
    {!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!} 
    {!! HTML::script('js/jquery.validate.min.js') !!}
    {!! HTML::style('css/bootstrap-select.css') !!}
    {!! HTML::script('js/bootstrap-select.js') !!} 

<style type="text/css">
.table th, .table td { 
     border-bottom: none !important;
 }
 </style>
@stop


@section('content')

 <?php $button_name='Complete Donation';
?>

 <?php
        $help_text='';
        if(count($entities))
        {

                if(strpos($frequency_text,'Every Month') == false&&strpos($frequency_text,'Every 3 Months') == false&&strpos($frequency_text,'Every 6 Months') == false&&strpos($frequency_text,'Every Year') == false)
                {
                    $option = "Complete Donation";
                    $CChelp_text='<small>Click "Complete Donation" to charge your card <strong>'. $vars['symbol'].number_format($total,2,'.','').'</strong>  </small>';
                    $NoCChelp_text='<small>Click "Complete Donation" to make '.count($entities).' donations '.(count($entities)>1? 's':'').'; '.$frequency_text.'</small>';
                }
                else
                {
                    $option = "Complete Sponsorship";
                    $CChelp_text='<small>Click "Complete Sponsorship" to charge your card <strong>'. $vars['symbol'].number_format($total,2,'.','').'</strong> and sign up for the following sponsorship'.($num_sponsorships>1? 's':'').': <br>'.strtolower($frequency_text).'</small>';
                    $NoCChelp_text='<small>Click "Complete Sponsorship" to sign up for '.$num_sponsorships.' sponsorship'.($num_sponsorships>1 ? 's':'').'; '.strtolower($frequency_text).'</small>';
                }

            if($useCC)
                $help_text=$CChelp_text;
            else
                $help_text=$NoCChelp_text;

            $login_help_text= str_replace($option,'Login and '.$option,$help_text);
            $NoCClogin_help_text= str_replace($option,'Login and '.$option,$NoCChelp_text);
        }
        ?>
@if(!empty($text_checkout))
<div class='panel panel-default'>
    <div class=" panel-body">
    	{!!$text_checkout!!}
    </div>
</div>
@endif

<div class="panel panel-default">
<div class="panel-heading" ><h3 class="panel-title">My Order <span class="badge pull-right">{!!count($entities)+count($saved_designations)!!}</span></h3> </div>

    <div class="table-responsive">
    <table class="table">
    <thead>
        <tr>
        <th></th>
        <th>Name</th>
        @if($hide_frequency)<th><div class="" type="" >Paid How Often</div></th> @endif
        <th><div class="">How Much</div></th>
        <th> </th>
        </tr>
    </thead>
        <tbody>
         
    @foreach($entities as $id => $amount)
        <tr>
            <td>
            <?php if($frequencies[$id]!='5')
                $button_name='Complete Sponsorship';
                ?>
                @foreach ($profilePics[$id] as $profilePic)
                    <div class="pull-left">
                    <a href="{!! URL::to('frontend/view_entity') !!}/{!!$client_id!!}/{!!$program_id!!}/{!!$id!!}/{!!$session_id!!}">
                      <img src="{!! $profilePic !!}" class="img-rounded" width="50"/>
                    </a>
                    </div>
                 @endforeach

                  
            </td>
            <td>
                <h5><a href="{!! URL::to('frontend/view_entity') !!}/{!!$client_id!!}/{!!$program_id!!}/{!!$id!!}/{!!$session_id!!}">{!!$titles[$id]!!}</a></h5>
            </td>   

            
            @if($amount_permissions[$id]['hide_frequency']!='hidden')
                <td>
                     <div class="form-group pull-left" >
                        <select  name="frequency{!!$id!!}" onchange="if (this.value) window.location.href=this.value; this.disabled=true;" class='form-control selectpicker'>
                            
                            @foreach ($frequency_options as $num=> $f_text)
                                <option {!!$num == $frequencies[$id] ? "selected" : ""!!} value="/frontend/checkout_update_frequency/{!!$client_id!!}/{!!$program_id!!}/{!!$id!!}/{!!$num!!}/{!!$session_id!!}">{!! $f_text !!}</option>
                            @endforeach

                        </select>
                    </div>
                </td>
            @endif
           
            <td>

            {!!Form::open()!!}
            @if ($amount_permissions[$id]['amount']==null)

                @if(empty($amount_permissions[$id]['sp_amount'][0]))
                    <div class="form-group pull-left">
                    {!!Form::text('sp_amount'.$id,$amount,$attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled'))!!}
                    </div>
                @else
                    <div class="form-group pull-left">
                        <select  name="sp_amount{!!$id!!}" onchange='if (this.value) window.location.href=this.value; this.disabled=true;' class='form-control selectpicker'>
                            @foreach ($amount_permissions[$id]['sp_amount'] as $the_amount)
                                <option {!!$the_amount == $amount ? "selected" : ""!!} value="/frontend/checkout_update_amount/{!!$client_id!!}/{!!$program_id!!}/{!!$id!!}/{!!$the_amount!!}/{!!$vars['symbol']!!}/{!!$session_id!!}">{!! $vars['symbol'] !!}{{ $the_amount }}@if ($frequencies[$id]!='5')/Month @endif</option>
                            @endforeach
                        </select>

                    </div>
                @endif
            @endif
        
            @if ($amount_permissions[$id]['amount']!=null)
                <div class="form-group pull-left">
                    <!-- {!! Form::label('amount', 'Sponsorship Amount') !!} -->
                    @if($frequencies[$id]=='5')
                        {!! Form::text('amount', $vars['symbol'].$amount, $attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled')) !!}
                    @else
                        {!! Form::text('amount', $vars['symbol'].$amount.'/Month', $attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled')) !!}
                    @endif
                </div>
                {!! Form::hidden('sp_amount'.$id, $amount) !!}
            @endif
            {!!Form::close()!!}
            </td>
         
            @if($disable_program_link!='1')
            <td>
                <div class="pull-right">
                    {!!Form::open(array('url' => URL::to('frontend/checkout_remove_entity/'.$client_id.'/'.$program_id.'/'.$id.'/'.$session_id)))!!}
                    {!!Form::button('<span class="glyphicon glyphicon-remove"></span> Remove', array('class' => 'btn  btn-danger ','onclick'=> 'this.disabled=true;this.form.submit();'))!!}
                    {!!Form::close()!!}
                </div>
            </td>
            @endif



        </tr>
        @endforeach  

        @if(isset($saved_designations))

        @foreach($saved_designations  as $id => $amount)

         <?php if($d_frequencies[$id]!='5')
                $button_name='Sponsor';
                ?>
                
        <tr>

        <td>
        </td>
        <td>
            <h5>{!!$designations[$id]->name!!}</h5>
        </td>

          <td>
           <div class="form-group pull-left">
                <!-- {!! Form::label('designation_frequency'.$id, 'Frequency') !!} -->
                <select  name="d_frequency{!!$id!!}" onchange="if (this.value) window.location.href=this.value; this.disabled=true;" class='form-control selectpicker'>
                    @foreach ($d_frequency_options as $num=> $f_text)
                        <option {!!$num == $d_frequencies[$id] ? "selected" : ""!!} value="/frontend/checkout_update_designation_frequency/{!!$client_id!!}/{!!$program_id!!}/{!!$id!!}/{!!$num!!}/{!!$session_id!!}">{!! $f_text !!}</option>
                    @endforeach
                </select>
            </div>
        
        </td>

        <td>
            <div class="form-group pull-left">
            <input class='form-control' id='disabledInput' disabled='disabled' name='designation' type='text' value="{!!$vars['symbol']!!}{{$amount}} {!!$d_frequencies[$id]!='5' ? $d_frequency_options[$d_frequencies[$id]] : ''!!}">
            </div>
        </td>
      
        <td>
       
            <div class="pull-right input-groups">
            {!!Form::open(array('url' => URL::to('frontend/checkout_remove_designation/'.$client_id.'/'.$program_id.'/'.$id.'/'.$vars['symbol'].'/'.$session_id)))!!}
            {!!Form::button('<span class="glyphicon glyphicon-remove"></span> Remove', array('class' => 'btn btn-danger','onclick'=> 'this.disabled=true;this.form.submit();'))!!}
            {!!Form::close()!!}
            </div>
        
        </td>
        </tr>
      @endforeach
    @endif
        
        <tr>
           <td></td>
           @if($hide_frequency) <td></td> @endif
            <td >
                <div class="pull-right">
                    Total Donation:
                </div>
            </td>
            <td>
            <div class='pull-left'><strong> {!!$vars['symbol']!!}{{number_format($total,2,'.','')}}</strong></div>
            </td>
            <td>
            </td>
            </tr>
        

      </tbody>
    </table>
    </div>
</div>

@if($session_logged_in=='true')

<div class="col-md-6 ">
 <div class="panel panel-default">

 <div class="panel-heading"><h3 class="panel-title">
      You are logged in <strong>{!!$session_donor_name!!}</strong>
 </h3></div>

 <div class="panel-body">
    {!! Form::open(array('url'=>'frontend/checkout_login/'.$client_id.'/'.$program_id.'/'.$session_id)) !!}

        @if(count($payment_options)>0)
        <input class="form-control" id='page' type="hidden" name="page" value="cc">
        {!!Form::label('method_signed_in','Choose your payment method', array('class'=> $hide_payment_method))!!} 

        {!! Form::select('method_signed_in', $payment_options, $default_payment_method, array('class' => 'form-control selectpicker '. $hide_payment_method)) !!}

            <br>
        @if(!$isDonorCardActive)
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
                    <div class="form-group">
                        <div class="row">
                            <div class="col-md-6">
                                {!!Form::label('firstName','First Name')!!} 
                                {!!Form::text('firstName','',$attributes=array('class'=>'form-control','placeholder' =>'First Name','value required' =>""))!!}
                            </div>
                             <div class="col-md-6">
                                {!!Form::label('lastName','Last Name')!!} 
                                {!!Form::text('lastName','',$attributes=array('class'=>'form-control','placeholder' =>'Last Name','value required' =>""))!!}
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        
                        <div class="row">
                            <div class="col-md-7">
                                {!!Form::label('number','Credit Card Number')!!}
                                {!!Form::text('number','',$attributes=array('class'=>'form-control','placeholder' =>'Credit Card Number','value required' =>""))!!}
                            </div>
                            <div class="col-md-5">
                                {!!Form::label('cvv','Security Code')!!}
                                {!!Form::text('cvv','',$attributes=array('class'=>'form-control','placeholder' =>'Security Code','value required' =>""))!!}
                            </div>
                        </div>
                    </div>
                    <div class="form-inline">
                            <div class="col-md-4">
                            {!!Form::label('expiry','Expiration Date: ',array('class'=>''))!!}
                            </div>
                            <div class="col-md-4">
                            {!!Form::select('expiryMonth',$months,'',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                            </div>
                            <div class="col-md-4">
                            {!!Form::select('expiryYear',$years,'',$attributes=array('class'=>'form-control','placeholder' =>'MM','value required' =>""))!!}
                            </div>
                        </div>
                </div>
            </div>
        @endif
        </div>
        @endif
        <br>
        <div class='form-group col-md-8' id="cc-signed_in_help_text">
            {!!$help_text!!}
        </div>    
        
        {!! Form::submit('Sponsor', array('class' => 'btn btn-primary pull-right form-control')) !!}

        @endif
      
    {!! Form::close() !!}
    
    </div>
    </div>
</div>

@endif


@if($session_logged_in=='true')
<div class="col-md-6">
@else
<div class="col-md-6 col-md-push-6">
@endif

@if (isset($designations)&&$designations_allowed=='1')
<div class="panel panel-default "  >
    <div class="panel-heading"><h3 class="panel-title">
        @if(count($designations)>1)
            Additional Gifts
        @else
            {!!reset($designations)->name!!}
        @endif
    <span class="pull-right glyphicon glyphicon-gift"></span></h3> </div>
           <div class="panel-body">

              {!!Form::open( array( 'url' => 'frontend/checkout_add_designation/'.$client_id.'/'.$program_id.'/'.$vars['symbol'].'/'.$session_id,'id'=>'addamount'))!!}
                 <div class='form-group' {!!(count($designations)<2 ? 'hidden' : '')!!} >
                    {!! Form::label('designation', 'Gift Options') !!}
                    <select  name="designation" class="form-control selectpicker" >
                        @foreach ($designations as $d)
                            <option value="{!!$d->id!!}">{!! $d->name !!}</option>
                        @endforeach
                    </select>

                    </div>

                    <div class='form-group'>
                    {!! Form::label('designation_amount', 'Gift Amount') !!}
                    {!!Form::text('designation_amount','',array('placeholder' => 'Enter amount','class' =>'form-control','required'=>""))!!}
                    </div>
                    <div class='form-group '>
                   {!! Form::submit('Add Additional Gift', array('class' => 'btn btn-default form-control')) !!}
                    {!! $errors->first('designation_amount', '<p class="text-danger">:message</p>') !!}
                    </div>
                    {!!Form::close()!!}
          </div>
    </div>
      @endif
@if($session_logged_in=='false')
    @if($login_box=='1')
   <div class="panel panel-default" >
        <div class="panel-heading"><h3 class="panel-title">Already have an account?  <span class="pull-right glyphicon glyphicon-log-in"></span></h3></div>
        <div class="panel-body">
        {!! Form::open(array('url'=>'frontend/checkout_login/'.$client_id.'/'.$program_id.'/'.$session_id,'id'=>'login')) !!}
        <p class="help-block">If you already have a donor account, you can login below to add {!!($num_of_sponsorships > 1 ? 'these sponsorships' : 'this sponsorship')!!} to your account.</p>

        <!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
        <input style="display:none" type="text" name="fakeusernameremembered"/>
        <input style="display:none" type="password" name="fakepasswordremembered"/>

        <div class="row">
            <div class="col-md-6 form-group">
            {!! Form::label('login_username', 'Username') !!}
                {!! Form::text('login_username', '', array('placeholder' => 'Enter your Username', 'class' => 'form-control' , 'required'=>"")) !!}
            </div>

            <div class="col-md-6 form-group">
                {!! Form::label('login_password', 'Password') !!}
                <!-- <span class="label label-primary required pull-right">Required</span> -->
                    {!! Form::password('login_password', $attributes = array('placeholder' => 'Enter your password', 'class' => 'form-control' , 'required'=>"")) !!}
            </div>

        </div>
        

            

        <div class="row">
            <div class="col-md-6 {!!$hide_payment_method!!}">
                    {!! Form::label('method_login', 'Method') !!}
                    {!! Form::select('method_login', $payment_options, $default_payment_method, array('class' => 'form-control selectpicker')) !!}
                 @if ( $useCC == false && empty($payment_options))
                    <div class="form-group">
                    Error: "Admin must enable payment options in program settings."
                    </div>
                @endif
            </div>
        </div>

        
        <div id="cc-form_login" class="form-group"> </div>



            <div class="alert alert-info"  id="cc-login_help_text">
                {!!$login_help_text!!}
            </div>

            <div class="form-group">
            {!! Form::submit('Login and '.$button_name, array('class' => 'btn btn-primary form-control' )) !!}
            </div>
            <div class="form-group">
            <a class="btn btn-default pull-right" href="{!!URL::to('frontend/reset_password',array($client_id,$program_id,$session_id))!!}}">I forgot my password</a>

            </div>

    {!! Form::close() !!}
        </div>
    </div>
@endif
</div>
    <div class="col-md-6 col-md-pull-6">
    <div class=" panel panel-default" >
    <div class="panel-heading"><h3 class="panel-title">New Sponsor Sign-up  <span class="pull-right glyphicon glyphicon-list-alt"></span></h3></div>

    <div class="panel-body">

    @if($login_box=='')
    <p class="help-block">Please fill out the form below to complete your sponsorship{!!($num_of_sponsorships > 1 ? 's' : '')!!}.</p>
    @endif
    @if($login_box=='1')
    <p class="help-block">If you are a new sponsor, please fill out the form below to complete your sponsorship{!!($num_of_sponsorships > 1 ? 's' : '')!!}.</p>
    @endif
    {!! Form::open(array('url'=>'frontend/checkout_signup/'.$client_id.'/'.$program_id.'/'.$session_id,'id'=>'signup')) !!}

        @foreach ($signup_fields as $k=> $field)
            <?php $field_type= $field->field_type?>
            {!! Form::$field_type($field,'') !!}
        @endforeach
        
                <!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
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

                <div class="col-md-6 {!!$hide_payment_method!!} form-group">
                    {!! Form::label('method_signup', 'Payment Method') !!}
                    {!! Form::select('method_signup', $payment_options, $default_payment_method, array('class' => 'form-control selectpicker')) !!}
                </div>

            </div>

        @if ( $useCC == false && empty($payment_options))
        <div class="form-group">
            Error: "Admin must enable payment options in program settings."
        </div>
        @endif
       
        <div class="form-group">
            
            {!! $errors->first('singup_password', '<p class="text-danger">:message</p>') !!}
           
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

@stop
@section('footerscripts')
<script>
$(document).ready(function() {

        var CCForm= '<div class="panel panel-primary">'+
                        '<div class="panel-heading"><h3 class="panel-title pull-left"><span class="glyphicon glyphicon-credit-card"></span> Your Credit Card</h3>'+
                        @if($useCC=='stripe')
                            '<a class="pull-right" href="https://stripe.com" target="_blank"><img class="" src="{!!URL::to('img/solid.png')!!}"></a>'+
                            '<div class="clearfix"></div>'+
                        @endif
                        '</div>'+
                            '<div class="panel-body">'+
                                    '<div class="row">'+
                                        '<div class="col-md-6 form-group">'+
                                            '{!!Form::label("firstName","First Name")!!} '+
                                            '{!!Form::text("firstName","",$attributes=array("class"=>"form-control","placeholder" =>"First Name","value required" =>""))!!}'+
                                        '</div>'+
                                         '<div class="col-md-6 form-group">'+
                                           '{!!Form::label("lastName","Last Name")!!} '+
                                            '{!!Form::text("lastName","",$attributes=array("class"=>"form-control","placeholder" =>"Last Name","value required" =>""))!!}'+
                                        '</div>'+
                                '</div>'+
                                    '<div class="row">'+
                                        '<div class="col-md-7 form-group">'+
                                            '{!!Form::label("number","Credit Card Number")!!}'+
                                            '{!!Form::text("number","",$attributes=array("class"=>"form-control","placeholder" =>"Credit Card Number","value required" =>""))!!}'+
                                        '</div>'+
                                        '<div class="col-md-5 form-group">'+
                                            '{!!Form::label("cvv","Security Code")!!}'+
                                            '{!!Form::text("cvv","",$attributes=array("class"=>"form-control","placeholder" =>"Security Code","value required" =>""))!!}'+
                                        '</div>'+
                                '</div>'+
                                '<div class="form-inline">'+
                                    '<div class="col-md-4 form-group">'+
                                    '{!!Form::label("expiry","Expiration Date: ",array("class"=>""))!!}'+
                                    '</div>'+
                                    '<div class="col-md-4 form-group">'+
                                    '{!!Form::select("expiryMonth",$months,"",$attributes=array("class"=>"form-control ","placeholder" =>"MM","value required" =>""))!!}'+
                                    '</div>'+
                                    '<div class="col-md-4 form-group">'+
                                    '{!!Form::select("expiryYear",$years,"",$attributes=array("class"=>"form-control ","placeholder" =>"MM","value required" =>""))!!}'+
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


        $('#method_login').change(function() {
            var selected = $(this).val();
            var useCC = '{!! $useCC !!}';
            
            if ( selected == 3 ) {
                if ( useCC != false )
                {
                        $('div#cc-login_help_text').html('{!!$login_help_text!!}');
                }
            } else {
                $('div#cc-login_help_text').html('{!!$NoCClogin_help_text!!}');
            }

        });
         $('#method_signed_in').change(function() {
            var selected = $(this).val();
            var useCC = '{!! $useCC !!}';
            var activeCC = '{!! $isDonorCardActive !!}'
            
            if ( selected == 3 ) {
                if ( useCC != false )
                {
                    if(activeCC == false)
                    {
                        $('div#cc-form_signup').html(CCForm);
                    }
                        $('div#cc-signed_in_help_text').html('{!!$help_text!!}');
                }
            } else {
                $('div#cc-form_signup').html('');
                $('div#cc-signed_in_help_text').html('{!!$NoCChelp_text!!}');
            }

        });

});


</script>
       
@stop