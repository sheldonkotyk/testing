@extends('admin.default')

@section('headerscripts')
	{!! HTML::style('css/demo_table.css') !!}
	{!! HTML::style('DataTables-1.10.4/media/css/jquery.dataTables.css') !!}
	{!! HTML::style('DataTables-1.10.4/extensions/TableTools/css/dataTables.tableTools.css') !!}
	
	{!! HTML::script('DataTables-1.10.4/media/js/jquery.dataTables.js') !!}
    {!! HTML::script('DataTables-1.10.4/extensions/TableTools/js/dataTables.tableTools.js') !!}
@stop

@section('content')
<?php
if($trashed=='1')
	$label=' <small><span class="glyphicon glyphicon-trash"></span> <em> Archived Recipients </em></small>';
elseif ($trashed==false) 
	$label=' <small><span class="glyphicon glyphicon-align-justify"></span> <em> All Recipients </em></small>';
elseif($trashed=='available')
	$label=' <small><span class="glyphicon glyphicon-star"></span> <em> Available Recipients </em></small>';
elseif($trashed=='sponsored')
	$label=' <small><span class="glyphicon glyphicon-ok"></span> <em> Fully Sponsored Recipients </em></small>';
elseif($trashed=='unsponsored')
	$label=' <small><span class="glyphicon glyphicon-star-empty"></span> <em> Un-Sponsored Recipients </em></small>';
else
	$lable = '';
?>
	<h1>{!! $program->name !!} {!!$label!!} 

	        <div class="pull-right">
	        <small>Share:</small>
		        <a class="btn btn-xs btn-default btn-extend be-left" href="https://twitter.com/share?url={!!URL::to('frontend/view_all',array(Session::get('client_id'),$program->id))!!}&text=See {!!$program->name!!}:" target="_blank">
		                <i class="icon ion-social-twitter"></i>Tweet</a> 
		         <a class="btn btn-xs btn-default btn-extend be-left" href="https://www.facebook.com/sharer/sharer.php?u={!!URL::to('frontend/view_all',array(Session::get('client_id'),$program->id))!!}&display=popup" target="_blank">
		                <i class="icon ion-social-facebook"></i>Share</a> 
		         <a class="btn btn-xs btn-default btn-extend be-left" href="mailto:?subject=See%20{!!$program->name!!}&body=Click%20on%20the%20link%20to%20find%20out%20about%20{!!$program->name!!}%0D%0A{!!URL::to('frontend/view_all',array(Session::get('client_id'),$program->id))!!}">
		                 Email
		                  <i class="glyphicon glyphicon-envelope"></i>
		                 </a>
		            <a class="btn btn-xs btn-default btn-extend be-left" data-toggle="collapse" href="#collapseTwo">
		                <i class="glyphicon glyphicon-link"></i> Embed</a>
	    	</div>
	</h1>
	<div id="collapseTwo" class="panel panel-default panel-collapse collapse">
			<div class="panel-heading">
                <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
               	<div class="panel-actions">
                    	<div class="label label-success">Info</div>
                </div>
                <h3 class="panel-title"> <strong> {!!$program->name!!}</strong> Frontend Embed Link</h3>
            </div><!-- /panel-heading -->
	              <div class="panel-body">
	              <h4 >Iframe Embed Code </h4>
	              <pre class="prettyprint">&lt;iframe class="hysiframe" src="{!! URL::to('frontend/view_all', array(Session::get('client_id'), $program->id)) !!}" style="border:0px #FFFFFF none;" name="HYSiFrame" scrolling="no" frameborder="1" height="1500px" marginheight="0px" marginwidth="0px" width="100%"&gt;&lt;/iframe&gt;</pre>
	              <br>
	              <h4><strong>{!!$program->name!!}</strong> Program Link (Infinite Scrolling, limit of 5000): <a href="{!! URL::to('frontend/view_all', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! URL::to('frontend/view_all', array(Session::get('client_id'),$program->id)) !!}</a></h4>

	              <h4><strong>{!!$program->name!!}</strong> Program Link (Pagination, limit of 1000): <a href="{!! URL::to('frontend/view_pages', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! URL::to('frontend/view_pages', array(Session::get('client_id'),$program->id)) !!}</a></h4>

	              <h4><strong>{!!$program->name!!}</strong> Random Single Recipient Link: <a href="{!! URL::to('frontend/random', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! URL::to('frontend/random', array(Session::get('client_id'),$program->id)) !!}</a></h4>

	              </div>
	            </div>
	 
	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif

		@include('admin.views.programMenu')
		@if(count($program)&&!empty($program->link_id))
			<div class="alert alert-info">This program is a sub program<br>
			The recipients below belong to <a href="{!!URL::to('admin/show_all_entities/'.$program->link_id)!!}"> "{!!Program::find($program->link_id)->name!!}"</a></br>
			You may view, but not edit the recipients on this page. All editing must be done <a href="{!!URL::to('admin/show_all_entities/'.$program->link_id)!!}"> in the parent program</a><br></div>
		@endif

		<hr>
		<div id="settings_select" class="collapse">
			{{-- content AJAX loaded --}}
	    </div>
	    
		<div id="loading">
		<p>Loading...</p>
			<div class="progress progress-striped active">
			  <div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
			  </div>
			</div>
		</div>
	    
		<div id="entity_table">
			{{-- content AJAX loaded --}}
		</div>

	

@stop

@section('modal')
<!-- Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">Permanently Delete</h4>
      </div>
      <div class="modal-body">
        Are you sure? This cannot be undone. This will also delete all associated information and files.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <a class="btn btn-danger amodal" href="#">Delete</a>
      </div>
    </div>
  </div>
</div>
@stop

@section('footerscripts')
	<script>
	$(document).ready( function () {
		var show_view = true;
		$('#settings').on('click', function() {
			$('div#settings_select').collapse('toggle');  
		});
		
		$('#loading').hide();  // hide it initially
		
		$('a#view').on('click', function() {
			$('div#view_select').collapse('toggle');  
		});
		
		$.ajax({
			url: "{!! URL::to('admin/field_options', array($program->id, 'all')) !!}",
			data: {'type':'available'},
			cache: 'false',
			dataType: 'html',
			type: 'get',
			success: function(html, textStatus) {
				$('div#settings_select').html(html);
			}
		});
		
		$.ajax({
			url: "{!! URL::to('admin/show_all_entities_table', array($program->id, $trashed)) !!}",
			data: {},
			cache: 'false',
			dataType: 'html',
			type: 'get',
			beforeSend: function() {
		    	$('#loading').show();
			},
			complete: function(){
				$('#loading').hide();
			},
			success: function(html, textStatus) {
				$('div#entity_table').html(html);
			}
		});		

		$(document).on( "click", "input#choose_fields", function(e) {
			e.preventDefault();
			$('div#settings_select').collapse('toggle');
			var program = new Array();
			var donor = new Array();
			var report_name = $("input#report_name").val();
			$("input#report_name").val('');
			$("input.program:checked").each(function() {
				program.push($(this).attr('name'));
			});
	
			$("input.donor:checked").each(function() {
				donor.push($(this).attr('name'));
			});
			$.ajax({
				url: '{!! URL::to('admin/field_options', array($program->id)) !!}',
				data: {'program':program, 'donor':donor, 'report_name':report_name},
				cache: 'false',
				dataType: 'html',
				type: 'post',
				success: function(html, textStatus) {
					$.ajax({
						url: '{!! URL::to('admin/show_all_entities_table', array($program->id,$trashed)) !!}',
						data: {},
						cache: 'false',
						dataType: 'html',
						type: 'get',
						beforeSend: function() {
					    	$('#loading').show();
						},
						complete: function(){
							$('#loading').hide();
						},
						success: function(html, textStatus) {
							$('div#entity_table').html(html);
						}
					});
				}
			});
		});
		
	});
	</script>
@stop