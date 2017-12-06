@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	
	<h2>{!! $hysform->name !!}</h2>
	<div class="panel panel-primary">
		<dl class="dl-horizontal">
			<dt>Created On:</dt>
			<dd>{!! $form->created_at !!}</dd>
			
			@if ($form->created_at != $form->updated_at)
				<dt>Updated On:</dt>
				<dd>{!! $form->updated_at !!}</dd>
			@endif
			
			<dt>By:</dt> 
			<dd>{!! $admin->first_name !!} {!! $admin->last_name !!}</dd>
						
			@foreach ($form_info as $f)
				<dt>{!! $f['field_label'] !!}:</dt> 
				<dd>{!! $f['data'] !!}</dd>
			@endforeach
		</dl>
	</div>
	<a class="btn btn-default" href="{!! URL::to('admin/edit_archived_form', array($form->id)) !!}">Edit</a> <a class="btn btn-default" href="{!! URL::to('admin/list_archived_forms', array($type, $id, $hysform->id)) !!}">Return</a>
@stop