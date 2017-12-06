@extends('admin.default')

@section('content')

 @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif

<h1><small><a href="{!!URL::to('admin/forms')!!}"> <span class="glyphicon glyphicon-arrow-down"></span> {!!Client::find(Session::get('client_id'))->organization!!} Forms  </a></small></h1>

<h2>{!! $hysform->name !!} ({!!$type_name!!}) <small><span class="icon ion-navicon-round"></span> Manage Fields </small> {!!(empty($mailchimp_list_name) ? '' : '<small><span class="pull-right "><small> '.$hysform->name.' <span class="glyphicon glyphicon-arrow-right"></span> <img src="'. URL::to('img/Freddie_wink_1.png') .'" style="width:25px;" > '.$mailchimp_list_name.' </small></span></small>') !!} </h2>
@include('admin.views.fieldsMenu')

<div class="app-body">
 <div class="magic-layout">
                          
            <div id="panel-bsbutton" class="panel panel-default magic-element width-full">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="icon ion-navicon-round"></i></div>
                    <div class="panel-actions">
                            <span class="badge">{!!$fields->count()!!} Fields</span>
                    </div>
                    <h3 class="panel-title">{!! $hysform->name !!}: Manage Fields</h3>
                </div><!-- /panel-heading -->
                <div class="panel-body">


<div class="dd">
	<ol id="item_list">
	@foreach ($fields as $field)
		<li id="item_{!! $field->id !!}" class="dd-item"><div class="dd-handle dd3-handle">Drag</div><div class="dd3-content">{!! $field->field_label !!} 
		@if ($hysform->type != 'submit')
  		: <span class="text-muted">[{!! $field->field_key !!}]</span>
      @if($gateway&&$hysform->type=='donor')
        <!-- @if($field->is_title=='1')
          <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the name</small>
        @endif -->

        @if($field->field_type=='hysGatewayAddress')
          <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the Address</small>
    		@endif

        @if($field->field_type=='hysGatewayCity')
          <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the City</small>
        @endif

        @if($field->field_type=='hysGatewayState')
          <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the State</small>
        @endif

        @if($field->field_type=='hysGatewayZipCode')
          <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the Zip Code</small>
        @endif

      @endif

    @endif
		<div class="pull-right"><a data-toggle="modal" href="{!! URL::to('admin/edit_form_field', array($field->id, $hysform->type)) !!}" data-target="#modal" title="Edit"><span class="glyphicon glyphicon-pencil"></span></a> <span class="text-muted">:</span> <a data-toggle="modal" href="{!! URL::to('admin/delete_form_field', array($field->id, $hysform->type)) !!}" data-target="#modal" title="Delete"><span class="glyphicon glyphicon-remove"></span></a></div></div>
		</li>
	@endforeach
	</ol>
</div>
 
   <div class="btn-group pull-right"><a data-toggle="modal" href="{!! URL::to('admin/delete_form', array($hysform->id)) !!}" data-target="#modal" title="DeleteForm">
    <button type="button" class="btn btn-danger">
       <span class="glyphicon glyphicon-remove"></span> Delete Form
    </button></a></div>
</div>
</div>
</div>
</div>
@stop

@section('modal')
	<!-- Edit Modal -->
  <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
   <div class="modal-dialog">
  	<div class="modal-content">
  	
  	</div>
   </div>
  </div><!-- /.modal -->

	<!-- Delete Form -->
  <div class="modal fade" id="deletemodal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
   <div class="modal-dialog">
  	<div class="modal-content">
  	
  	</div>
   </div>
  </div><!-- /.modal -->

  <!-- Delete Form Modal -->
  <div class="modal fade" id="deleteformmodal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
   <div class="modal-dialog">
  	<div class="modal-content">
  	
  	</div>
   </div>
  </div><!-- /.modal -->

@stop

@section('footerscripts')
{!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!}
<script>
$(document).ready(function() {
  
	$("#item_list").sortable({stop:function(event, ui) {
		$.ajax({
		type: "GET",
		url: "/admin/update_field_order/{!! $hysform->type !!}",
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
	
	$("#manage_forms").collapse('show')

});
</script>
@stop