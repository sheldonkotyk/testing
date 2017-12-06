@extends('admin.default')

@section('headerscripts')
	<!-- {!! HTML::style('css/demo_table.css') !!} -->
	{!! HTML::style('DataTables-1.10.4/media/css/jquery.dataTables.css') !!}
	{!! HTML::style('DataTables-1.10.4/extensions/TableTools/css/dataTables.tableTools.css') !!}
	{!! HTML::script('DataTables-1.10.4/media/js/jquery.dataTables.js') !!}
    {!! HTML::script('DataTables-1.10.4/extensions/TableTools/js/dataTables.tableTools.js') !!}
    {!! HTML::script('assets/messenger/js/messenger.min.js')!!}
    {!!HTML::script('assets/messenger/js/messenger-theme-flat.js')!!}
    
@stop

@section('content')

<?php
if($trashed=='1')
	$label=' <small><span class="glyphicon glyphicon-trash"></span> <em> Archived Donors </em></small>';
elseif ($trashed==false) 
	$label=' <small><span class="glyphicon glyphicon-align-justify"></span> <em> All Donors </em></small>';
else
	$label = '';
?>

<h1>{!! $hysform->name !!} {!!$label!!} {!!(empty($mailchimp_list_name) ? '' : '<small><span class="pull-right "><small> '.$hysform->name.' <span class="glyphicon glyphicon-arrow-right"></span> <img src="'. URL::to('img/Freddie_wink_1.png') .'" style="width:25px;" > '.$mailchimp_list_name.' </small></span></small>') !!} </h1>

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

	@include('admin.views.donorsMenu')

	<hr>
	<div id="settings_select" class="collapse reverse-well row">
		{{-- content AJAX loaded --}}
    </div>
    
	<div id="loading">
	<p>Loading...</p>
		<div class="progress progress-striped active">
		  <div class="progress-bar"  role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
		  </div>
		</div>		
	</div>
    
	<div id="donor_table">
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
		$('#settings').on('click', function() {
			$('div#settings_select').collapse('toggle');  
		});
		
		$('#loading').hide();  // hide it initially


		    		
		$.ajax({
			url: "{!! URL::to('admin/donor_field_options', array($hysform_id, 'donor')) !!}",
			data: {'type':'available'},
			cache: 'false',
			dataType: 'html',
			type: 'get',
			success: function(html, textStatus) {
				$('div#settings_select').html(html);
			}
		});
		
		$.ajax({
			url: "{!! URL::to('admin/show_all_donors_table', array($hysform_id, $trashed)) !!}",
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
				$('div#donor_table').html(html);
			}
		});		

		$(document).on( "click", "input#choose_fields", function(e) {
			e.preventDefault();
			$('div#settings_select').collapse('toggle');
			var program = new Array();
			var donor = new Array();
			var report_name = $("input#report_name").val();
			$("input#report_name").val('');
			$("input.donor:checked").each(function() {
				donor.push($(this).attr('name'));
			});

			$.ajax({
				url: "{!! URL::to('admin/donor_field_options', array($hysform_id)) !!}",
				data: {'donor':donor, 'report_name':report_name},
				cache: 'false',
				dataType: 'html',
				type: 'post',
				success: function(html, textStatus) {
					$.ajax({
						url: "{!! URL::to('admin/show_all_donors_table', array($hysform_id,$trashed)) !!}",
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
							$('div#donor_table').html(html);
						}
					});
				}
			});
		});
		
	});
	</script>
@stop