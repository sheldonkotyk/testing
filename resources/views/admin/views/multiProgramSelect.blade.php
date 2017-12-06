@extends('admin.default')

@section('headerscripts')

{!! HTML::style('css/chosen.min.css') !!}
	

@stop
@section('content')

<h2>{!!Client::find(Session::get('client_id'))->organization!!} URL Generator <small> <span class="glyphicon glyphicon-th"></span> Create Frontend URL</small></h2>

    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif


<div class="app-body">
@if($exists)
	@foreach($programs_by_hysform_id as $key => $programs)
		@if(count($programs)>1&&$key!=0)
			<div class='magic-layout'>
			{!!Form::open()!!}
				<div id="panel1" class="panel panel-default magic-element width-full">
		            <div class="panel-heading">
		                <div class="panel-icon"><i class="glyphicon glyphicon-th"></i></div>
		                <h3 class="panel-title">URL Generator</h3>
		            </div><!-- /panel-heading -->
		            <div class="panel-body">

		        <div class="form-group">
						<label for="program_settings">Choose Program to Draw settings from</label>
						<select data-placeholder="Select Default Program Settings" name="program_settings" class="chosen-select form-control program_settings" >
							<option value=''></option>
						@foreach ($programs as $program)
							<option value="{!! $program->id !!}">{!! $program->name !!}</option>
						@endforeach
						</select>
						<p class="help-block">Once you have made your selections click "Make URL"</p>
					</div>
				    
		            Select the programs you wish to display<br/>

				@foreach ($programs as $program)
				{!!Form::checkbox('program_list[]',$program->id)!!}
				{!!Form::label($program->name)!!}
				<br/>
				@endforeach

				{!! Form::submit('Make URL', array('class' => 'btn btn-primary pull-right')) !!}

			{!!Form::close()!!}



			</div>
			</div>
			</div>

		@endif
	@endforeach

@else
<div class='magic-layout'>
	<div id="panel1" class="panel panel-default magic-element width-full">
	    <div class="panel-heading">
	        <div class="panel-icon"><i class="glyphicon glyphicon-th"></i></div>
	        <h3 class="panel-title">Multiple Program URL Generator</h3>
	    </div><!-- /panel-heading -->
	    <div class="panel-body">
			<div class="alert alert-warning">
            	 <span class="glyphicon glyphicon-warning-sign"></span>
            	 No Programs are available for creating a Multi-Program URL.<br/>
				 Programs must share the same Recipient form in order to be used on the same page.
            </div><!-- /alert-info -->
		</div>
	</div>
</div>
</div>

@endif

@stop

@section('footerscripts')

	{!! HTML::script('js/chosen.jquery.min.js') !!}
	<script>
	$(document).ready(function() {
		
		$(".program_settings").chosen({
		    width: "95%"
		});
		
		});
	</script>

@stop