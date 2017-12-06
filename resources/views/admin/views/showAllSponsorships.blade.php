@extends('admin.default')

@section('headerscripts')
	<!-- {!! HTML::style('css/demo_table.css') !!} --> 
	{!! HTML::style('DataTables-1.10.4/media/css/jquery.dataTables.css') !!}
	{!! HTML::style('DataTables-1.10.4/extensions/TableTools/css/dataTables.tableTools.css') !!}
	{!! HTML::script('DataTables-1.10.4/media/js/jquery.dataTables.js') !!}
    {!! HTML::script('DataTables-1.10.4/extensions/TableTools/js/dataTables.tableTools.js') !!}
@stop

@section('content')

	<?php

	if($program=='all')
		$program_id= 'all';
	else
		$program_id=$program->id;

	?>
	@if($program== 'all')

		<h1>All Sponsorships</h1>

	@else

		<?php
		if($trashed=='1')
			$label=' <small><span class="glyphicon glyphicon-trash"></span> <em>Archived Sponsorships</em></small>';
		elseif ($trashed==false) 
			$label=' <small><span class="glyphicon glyphicon-link"></span> <em>Sponsorship Summary</em></small>';
		elseif($trashed=='available')
			$label=' <small><span class="icon ion-contrast"></span> <em>Available for Sponsorship</em></small>';
		elseif($trashed=='sponsored')
			$label=' <small><span class="icon ion-link"></span> <em>Fully Sponsored</em></small>';
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
	              <h4><strong>{!!$program->name!!}</strong> Program Link: <a href="{!! URL::to('frontend/view_all', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! URL::to('frontend/view_all', array(Session::get('client_id'),$program->id)) !!}</a></h4>
	              </div>
	            </div>

	@endif
	
	@if (Session::get('message'))
	    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
	@endif

	@if($program!='all')
	
		@include('admin.views.programMenu')

		@if(count($program)&&!empty($program->link_id))
			<div class="alert alert-info">Note: Because this program is a sub program, the sponsorships below belong to <a href="{!!URL::to('admin/show_all_sponsorships/'.$program->link_id)!!}"> "{!!Program::find($program->link_id)->name!!}"</a>.</br>
			However, this page will show all sponsorships that were input via the "{!!$program->name!!}" Sub Program.<br>
			From this page, you may not edit Recipients, you must do that in the <a href="{!!URL::to('admin/show_all_entities/'.$program->link_id)!!}"> Parent Program</a>.<br>
			</div>
		@endif
		
	@else

		<ul class="nav nav-pills">

			<li><div class="btn-group"><a href="#">
            <button type="button" class="btn btn-default" id="settings">
               <span class="glyphicon glyphicon-th-list"></span> Edit Report
            </button></a></div></li>
		</ul>

	@endif

	<br/>
	<br/>
	
	<div id="settings_select" class="collapse reverse-well">
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
	
@section('footerscripts')
	<script>
	$(document).ready( function () {
		$('#settings').on('click', function() {
			$('div#settings_select').collapse('toggle');  
		});
		
		$('#loading').hide();  // hide it initially
		
		$.ajax({
			url: "{!! URL::to('admin/field_options', array($program_id, 'sponsorship')) !!}",
			data: {'type':'available'},
			cache: 'false',
			dataType: 'html',
			type: 'get',
			success: function(html, textStatus) {
				$('div#settings_select').html(html);
			}
		});
		
		$.ajax({
			url: '{!! URL::to('admin/show_all_sponsorships_table', array($program_id,$trashed)) !!}',
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
			$("input.program:checked").each(function() {
				program.push($(this).attr('name'));
			});
	
			$("input.donor:checked").each(function() {
				donor.push($(this).attr('name'));
			});
			
			$.ajax({
				url: '{!! URL::to('admin/field_options', array($program_id)) !!}',
				data: {'program':program, 'donor':donor},
				cache: 'false',
				dataType: 'html',
				type: 'post',
				success: function(html, textStatus) {
					$.ajax({
						url: '{!! URL::to('admin/show_all_sponsorships_table', array($program_id,$trashed)) !!}',
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