@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/redactor.css') !!}
	{!! HTML::script('js/redactor.min.js') !!}
@stop

@section('content')
    
    
    <h1><small><a href="{!!URL::to('admin/email_manager')!!}"><span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Message Manager </a></small></h1>
	
	<h2> {!!ucfirst($from_title)!!} Message <small> <span class="icon ion-ios7-browsers"></span> Send Message from : {!! $from_name !!}</small></h2>
	
	@include('admin.views.donorEmailMenu')

	@if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<div class="app-body">

                                        
		<div id="panel-bsbutton" class="panel panel-default magic-element width-full">
            <div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-envelope"></i></div>
                <div class="panel-actions">
                    <div class="badge"></div>
                </div>
               
                <h3 class="panel-title">Send Message</h3>
            </div><!-- /panel-heading -->
            <div class="panel-body">

	<div class="row">
				<div class="mpc-details">
								@if($file!=null)
                                <div class="pull-right">
                                    <p class="help-block text-highlight text-right">
                                        <i class="glyphicon glyphicon-paperclip" title="Paperclip"></i>
                                        &nbsp;&nbsp;
                                    </p>
                                </div>
                                @endif
                                <div class="mpc-avatar">
                                	@if(!empty($thumbnail))
                                    	<img src="{!!$profileThumb!!}" alt="">
                                    @else

                                    @endif
                                </div>
                                <h3 class="mpc-sender-name">{!!$from_name!!}</h3>
                                <div class="mpc-sender-mail">
                                    From {!!ucfirst($from_title)!!} to {!!count($recipients)!!} {!!$to!!}
                                </div>
                            </div>
			{!! Form::open(array('url' => 'admin/send_message/'.$entity_id.'/'.$donor_id.'/'.$from.'/'.$file_id,'class'=>'reverse-well','style'=>'width:90%;')) !!}
			<div class="form-group">
				To {!!ucfirst($to)!!}: 
				@foreach($recipients as $r)
					@if($from=='donor')
						 <a href="{!!URL::to('admin/send_message',array($r['id'],$donor_id,$from,$file_id))!!}"><p class='label label-primary'>{!!$r['name']!!}</p></a>
					@elseif($from=='entity')
						<a href="{!!URL::to('admin/send_message',array($entity_id,$r['id'],$from,$file_id))!!}"><p class='label label-primary'>{!!$r['name']!!}</p></a>
					@endif
				@endforeach
				<br><br>
				Subject
				{!!Form::text('subject',$value = null,array('class'=>'form-control','style="width:40%;'))!!}
				{!! Form::label('message', 'Message Body' ) !!}
				{!! Form::textarea('message', $value = null, $attributes = array('class' => 'form-control hysTextarea')) !!}
			</div>
			@if(empty($disabled))
				{!! Form::submit('Send', array('class' => 'btn btn-primary')) !!}
			@else
				{!! Form::submit('Send', array('class' => 'btn btn-primary','disabled'=>'disabled')) !!}
			@endif
			{!! Form::close() !!}
			</div>
			@if($file!=null)
			<div class="pull-right">
				Attached File: 
				@if($file['type']=='image')
	            	 {!! $file['file_name'] !!} <img src="{!!$thumbnail!!}" style="width:50px;">
	            @else
	            	<span class="glyphicon glyphicon-file"></span> {!! $file['file_name'] !!}
	            @endif
	            <a href="{!!URL::to('admin/send_message',array($entity_id,$donor_id,$from,null))!!}"><span class="glyphicon glyphicon-remove"></span></a>
            </div>

            @endif
	</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
		$('.hysTextarea').redactor();
	});
	</script>
@stop