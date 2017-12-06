@extends('admin.default')
@section('content')
<h2>{!! $client['name'] !!} Programs <small> <span class="icon ion-wrench"></span> All Programs</small></h2>

@include('admin.views.programsMenu')

@if (Session::get('message'))
    <p><div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div></p>
@endif

	<div class="app-body">
	                                        
			<div id="panel-bsbutton" class="panel panel-default width-full">
			
	            <div class="panel-heading">
	                <div class="panel-icon"><i class="icon ion-wrench"></i></div>
	                <div class="panel-actions">
	                    <div class="badge">{!!$number_of_programs!!} Programs</div>
	                </div>
	               
	                <h3 class="panel-title">All Programs</h3>
	            </div><!-- /panel-heading -->
	            
	            <div class="panel-body">
					<div class="dd">
						{!! $programs !!}
					</div>
				</div>
				
			</div>
			
	</div>

<h4>General Login Link</h4>
<pre>&lt;iframe class="hysiframe" src="{!! URL::to('frontend/login', array(Session::get('client_id'), 'none')) !!}" style="border:0px #FFFFFF none;" name="HYSiFrame" scrolling="no" frameborder="1" marginheight="0px" marginwidth="0px" height="500px" width="100%"&gt;&lt;/iframe&gt;</pre>
<p class="help-text">Use this code to embed a general login (not associated with a particular program).</p>
@stop

@section('modal')
<div class="modal fade" id="delete-modal">
  <div class="modal-dialog">
    <div class="modal-content">
    </div>
  </div>
</div>	
@stop

@section('footerscripts')
{!! HTML::script('js/jquery.nestable.js')!!}
	
	<script>
	$(document).ready(function() {
		$('.dd').nestable();
		
		$('.dd').on('change', function() {
			var tree = $('.dd').nestable('serialize');
			$.ajax ({
				type: "POST",
				url: "/admin/updatetree",
				data: {'data':tree},
			}) .done(function( msg ) {

			});
		});
	});
	
	</script>
@stop