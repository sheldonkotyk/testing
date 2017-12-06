@extends('admin.default')

@section('headerscripts')
@stop

@section('content')
    @if (Session::get('message'))
        <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
    @endif
	<h2>Front End Template Editor</h2>
	<div class="reverse-well">
		<p>Use this to enter custom CSS, Javascript/Jquery, and HTML to the front end (public) templates. This should be done by an experienced web developer as changes here could potentially effect the performance of the software. HelpYouSponsor only provides pre-paid support for what is entered here.</p>
	{!! Form::model($template) !!}
		<div class="form-group">
			{!! Form::label('css', 'Enter CSS') !!}
			{!! Form::textarea('css', $value = null, $attributes = array('class' => 'form-control')) !!}
		</div>
		
		<div class="form-group">
			{!! Form::label('js', 'Enter Javascript or Jquery') !!}
			{!! Form::textarea('js', $value = null, $attributes = array('class' => 'form-control')) !!}
		</div>
		
		<div class="form-group">
			{!! Form::label('html', 'Enter HTML') !!}
			{!! Form::textarea('html', $value = null, $attributes = array('class' => 'form-control')) !!}
		</div>

		{!! Form::submit('Save', array('class' => 'btn btn-primary')) !!}
		<a href="{!! URL::to('admin/edit_client_account') !!}" class="btn btn-default">Cancel</a>
	{!! Form::close() !!}
	</div>

	<div class="reverse-well">
	{!! Form::open(array('files' => true, 'url' => 'admin/upload_pic_to_template')) !!}
	    
	    <div class="form-group">
	        {!! Form::label('file', 'Upload Image File') !!}
	        <div class="input-group">
	        	{!! Form::file('file') !!}
	        </div>
	    </div>
	    
	{!! Form::submit('Upload File', array('class' => 'btn btn-primary')) !!}
	{!! Form::close() !!}
	</div>
	
	<hr>
	
	<h4>Available Image Files</h4>
	<table class="table">
		<thead>
		<tr>
			<th>Filename</th>
			<th>Link</th>
			<th>Delete</th>
		</tr>
		</thead>
		<tbody>
		@foreach ($pics as $pic)
			<tr>
				<td>{!! $pic->file_name !!}</td>
				<td>https://s3-us-west-1.amazonaws.com/hys/{!! $pic->name !!}</td>
				<td><a href="{!! URL::to('admin/remove_template_pic', array($pic->id)) !!}"><span class="glyphicon glyphicon-remove"></span></a></td>
			</tr>
		@endforeach
		</tbody>
	</table>
@stop

@section('footerscripts')
	<script>
	$(document).ready(function() {
	});
	</script>
@stop