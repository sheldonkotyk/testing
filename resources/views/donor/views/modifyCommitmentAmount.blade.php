@extends('frontend.default')

@section('content')

<div class="pull-left well" style="width:45%">

 <legend>Modify Amount / Schedule for <small><em>{!!$designation['name']!!}</em></small> </legend>

    



    Currently: 
        @if($sponsorship['frequency']=='Monthly')
            <span>{!!$sponsorship['currency_symbol']!!}{{$sponsorship['commit']}} {!!$sponsorship['frequency']!!} <em>(via {!!$sponsorship['method']!!})</em> </span>
        @else
            <span>{!!$sponsorship['currency_symbol']!!}{{$sponsorship['commit']}} per Month <em>(paid {!!$sponsorship['frequency']!!} via {!!$sponsorship['method']!!})</em></span>
        @endif
        <br><br>
    
    {!!Form::open()!!}

        <div class="form-group">
            {!!Form::label('new_amount','New Monthly Amount')!!}
            {!!Form::text('new_amount', '', $attributes = array('placeholder' => '', 'class' => 'form-control'))!!}
            {!! $errors->first('new_amount', '<p class="text-danger">:message</p>') !!}
            @if(!empty($frequency_options))
                {!!Form::label('new_frequency','New Schedule')!!}
                {!! Form::select('frequency', $frequency_options, '', array('class' => 'form-control')) !!}
                <strong>Note: Changing the schedule, resets your payment date to today.</strong>
            @endif
          
        </div>
        {!!Form::submit('Modify',array('class' => 'btn btn-primary'))!!}
        <a href="{!! URL::previous() !!}" class="btn btn-default">Cancel</a>
    {!!Form::close()!!}
</div>


@stop