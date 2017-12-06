@extends('frontend.default')



@section('headerscripts')

    <meta property="og:title" content="Sponsor {!!strip_tags($title)!!}"/>
    <meta property="og:url" content="{!!URL::to('frontend/view_entity',array($client_id,$program_id,$entity_id))!!}"/>
    <meta property="og:image" content="{!! $profilePicThumb !!}"/>
    <meta property="og:site_name" content="{!!strip_tags($client->organization)!!}"/>
    <meta property="og:description" content="Meet {!!strip_tags($title)!!}"/>
    
    {!! HTML::style('css/jquery-ui.min.css') !!}
    {!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!} 
    {!! HTML::script('js/jquery.validate.min.js') !!}
@stop
@section('content')

    <div class=" pull-left" style="width:45%">
        <div class="panel panel-default" >
        
        @if ($vars['program_type'] == 'funding'||$vars['program_type'] == 'one_time')
          <div class="panel-heading"> <h3 class="panel-title">Donate Now</h3></div>
          <div class="panel-body">
            <p class="help-text">Click "Donate Now" to add <strong>{!!$title!!}</strong> to your order. </p>
        @else
           <div class="panel-heading"><h3 class="panel-title"> Sponsor {!!$title!!}</h3></div>
           <div class="panel-body">
            <p class="help-text">Click "Sponsor Now" to add <strong>{!!$title!!}</strong> to your order. </p>
        @endif
        
        {!! Form::open(array('url'=>'/frontend/save_entity/'.$client_id.'/'.$program_id.'/'.$entity_id.'/'.$session_id,'id'=>'add_to_order')) !!}

        
        @if($display_percent)
        @if ($vars['program_type'] =='funding')
           <strong>Progress since {!!$start_date!!}</strong>
        @endif
       <div class="progress" style="max-width:100%;text-align:center;margin: 0 auto;">
            <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {!!$entity_percent!!}%; min-width: 2em;">
              {!!$entity_percent!!}%
            </div>
        </div>
        @endif

        @if($display_info)
            <h3 style="text-align:left;">{!!$entity_info!!}</h3>
            <br>
        @endif
        
        @if ($vars['program_type'] == 'contribution')
        
                <div class="form-group">

                @if(empty($vars['sp_amount'][0]))
                     {!! Form::label('amount', 'Please Input Your Sponsorship Amount') !!}
                    {!!Form::text('sponsorship_amount','',array('class'=>'form-control'))!!}
                @else
                 {!! Form::label('sponsorship_amount', 'Please Select Your Sponsorship Amount') !!}
                <select class="form-control" name="sponsorship_amount">
                    @foreach ($vars['sp_amount'] as $amount) 
                        <option value="{!! $amount !!}">{!! $vars['symbol'] !!}{{ $amount }}</option>
                    @endforeach
                </select>
                @endif
                {!! $errors->first('sponsorship_amount', '<p class="text-danger">:message</p>') !!}
            </div>
        @endif

        @if ($vars['program_type'] == 'funding')
            <div class="form-group">
                {!! Form::label('sponsorship_amount', 'Donation Amount') !!}
                @if(empty($vars['sp_amount'][0]))
                    {!!Form::text('sponsorship_amount','',array('placeholder' => 'Numbers Only', 'class'=>'form-control'))!!}
                @else
                <select class="form-control" name="sponsorship_amount">
                    @foreach ($vars['sp_amount'] as $amount) 
                        <option value="{!! $amount !!}">{!! $vars['symbol'] !!}{{ $amount }}</option>
                    @endforeach
                </select>
                @endif
                {!! Form::hidden('frequency', '5') !!}
                {!! $errors->first('sponsorship_amount', '<p class="text-danger">:message</p>') !!}
            </div>
           
        @endif
        @if($vars['program_type'] == 'one_time')
            <div class="form-group">
                {!! Form::label('sponsorship_amount', 'Donation Amount') !!}
                @if(empty($vars['sp_amount'][0]))
                    {!!Form::text('sponsorship_amount','',array('placeholder' => 'Numbers Only', 'class'=>'form-control'))!!}
                @else
                <select class="form-control" name="sponsorship_amount">
                    @foreach ($vars['sp_amount'] as $amount) 
                        <option value="{!! $amount !!}">{!! $vars['symbol'] !!}{{ $amount }}</option>
                    @endforeach
                </select>
                @endif
                {!! Form::hidden('frequency', '5') !!}
                {!! $errors->first('sponsorship_amount', '<p class="text-danger">:message</p>') !!}
            </div>
        @endif
        
        @if ($vars['program_type'] == 'number')

            <div class="form-group">
                {!! Form::label('amount', 'Sponsorship Amount') !!}
                {!! Form::text('amount', $vars['symbol'].$vars['sp_amount'], $attributes = array('class' => 'form-control', 'id' => 'disabledInput', 'disabled')) !!}
            </div>
            {!! Form::hidden('sponsorship_amount', $vars['sp_amount']) !!}
        @endif


        @if($entity->status=='0')
    		@if (!empty($vars['sp_amount']))
                @if ($vars['program_type'] == 'funding'||$vars['program_type']=='one_time')
                    {!! Form::checkbox('monthly','1',false)!!} <em>Give Monthly</em><br/><br/>
                    {!! Form::submit('Donate Now', array('class' => 'btn btn btn-primary form-control', 'id' => 'donate_btn_'.$program_id)) !!}
                @else
                    @if($already_saved)
                         {!! Form::submit('Already on your order page', array('class' => 'form-control btn btn-primary', 'id' => 'sponsor_btn_'.$program_id)) !!}
                    @else
                        {!! Form::submit('Sponsor Now', array('class' => 'form-control btn btn-primary', 'id' => 'sponsor_btn_'.$program_id)) !!}
                    @endif
                 @endif
    		@endif
        @else
                {!! Form::submit('Already Fully Sponsored', array('class' => 'btn btn-primary form-control','disabled'=>'disabled')) !!}
            @endif
            </div>
    </div>
</div>

<div class=" pull-right" style="width:45%">
<div class="panel panel-default" style="">
<div class="panel-body">
<img id="profile_image" src="{!! $profilePic !!}" class="img-rounded img-responsive"/>



<br/>
    {!!$info!!}
    <br>

   @if(!empty($image_links))
Images:<br/>
@foreach($image_links as $k => $link)
    <span><a href="#"><img id="image_number_{!!$k!!}" src="{!!$link['thumbnail']!!}" width='100'></a></span>
@endforeach
<br/>
@endif

@if(!empty($file_links))
Files:
@foreach ($file_links as $k => $link)

    <br/><span><a href="{!!$link['file_link']!!}">{!!$link['file_name']!!}</a></span>
@endforeach
@endif 
</div>
</div>
</div>

<div class="pull-left" style="width:45%">
<div class="panel panel-default" style="">
<div class="panel-body">
        @foreach ($public_fields as $field)
    
            @if (!empty($profile[$field->field_key]))
                {!!$field->field_label!!}:
                <strong>{!!$profile[$field->field_key]!!}</strong>
                <br/><br/>
            @endif
            
        @endforeach
        
</div>
</div>
</div>



@if(!empty($text_profile))
<div class="pull-left" style="width:100%;">
    <div class="panel panel-default"> <div class="panel-body ">{!!$text_profile!!}</div></div>
</div>
@endif

<script>
 $(document).ready(function() {

@foreach($image_links as $k => $link)
$('#image_number_{!!$k!!}').on({
    'click': function(){
        $('#profile_image').attr('src',"{!!$link['original']!!}");
    }
});
@endforeach

});

</script>


@stop