@extends('admin.default')

@section('content')
	<h1>Manage Donor Program Settings for: <small>{!! $donorprogram->name !!}</small></h1>
<hr>
<h4>Manage Form Fields</h4>
<a href="{!! URL::to('admin/add_form_field') !!}/{!! $donorprogram->id !!}/Donorfield">Add New Form Field</a>
<div class="dd">
	<ol id="item_list">
	@foreach ($fields as $field)
		<li id="item_{!! $field->id !!}" class="dd-item"><div class="dd-handle dd3-handle">Drag</div><div class="dd3-content">{!! $field->field_label !!}<div class="pull-right"><a data-toggle="modal" href="{!! URL::to('admin/edit_form_field') !!}/{!! $field->id !!}/Donorfield" data-target="#modal" title="Edit"><span class="glyphicon glyphicon-pencil"></span></a> <span class="text-muted">:</span> <a data-toggle="modal" href="{!! URL::to('admin/delete_form_field') !!}/{!! $field->id !!}/Donorfield" data-target="#modal" title="Delete"><span class="glyphicon glyphicon-remove"></span></a></div></div>
		</li>
	@endforeach
	</ol>
</div>
@stop

@section('modal')
	<!-- Edit Modal -->
  <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
  
  </div><!-- /.modal -->

	<!-- Delete Modal -->
  <div class="modal fade" id="deletemodal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
  
  </div><!-- /.modal -->


<hr>
@stop

@section('footerscripts')
{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}
<script>
$(document).ready(function() {
  
	$("#item_list").sortable({stop:function(event, ui) {
		$.ajax({
		type: "GET",
		url: "/admin/update_field_order/Donorfield",
		data: $("#item_list").sortable("serialize")
		}) .done(function( msg ) {

		});
	}
	});
	
	$(".dd3-content").hover(function() {
		$("#item_list").sortable("disable");
	});
	$(".dd-handle").hover(function() {
		$("#item_list").sortable("enable");
	});
	
	$('body').on('hidden.bs.modal', '#modal', function () {
	    $(this).removeData('bs.modal');
	});

});
</script>
@stop