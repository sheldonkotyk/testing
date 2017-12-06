<table class="table table-striped table-condensed table-bordered">
	<tr>
		<th>File</th>
		@if($box_exists)
		<th>Full Size link</th>
		@endif
		<th>Type</th>
		<th>Permissions</th>
		<th>Date Created</th>
		<th>Date Updated</th>
		<th>Manage</th>
	</tr>
@if (!empty($files))
@foreach ($files as $file)
	@if ($file['profile'] == 1)
	<tr class="success">
	@else
	<tr>
	@endif
	@if ($file['type'] == 'Image')
		<td>
			<a href="{!! $file['link'] !!}"><img src="{!! $file['link'] !!}" class="img-rounded" style="width:50px" /></a>
			<small>{!! $file['file_name'] !!}</small>
		</td>
	@endif
	@if ($file['type'] == 'Document')
		<td>
		<a href="{!! $file['link'] !!}" class="btn btn-primary"><span class="glyphicon glyphicon-file"></span> {!! $file['file_name'] !!}</a>
		
		</td>
	@endif
		@if($box_exists)
			@if(!empty($file['box_name']))
			<td><a href="{!!$file['box_name']!!}">{!!$file['file_name']!!}<a></td>
			@else
			<td>No File</td>
			@endif
		@endif
		<td>
			{!! $file['type'] !!}
		</td>
		<td>
			{!! $file['permissions'] !!}
		</td>
		<td>
			{!! $file['created_at'] !!}
		</td>
		<td>
			{!! $file['updated_at'] !!}
		</td>
		<td style="min-width:85px">
			
				<a href="{!! URL::to('donor/delete_file', array($client_id,$program_id,$file['id'],$entity_id,$session_id)) !!}"><button type="button" class="btn btn-default"><span class="glyphicon glyphicon-remove"></span> Delete</button></a>		
		</td>
	</tr>
@endforeach
@else
<tr><td rowspan="6">No Files to Display.</td></tr>
@endif
</table>

<!-- Edit Modal -->
<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="modal-title" aria-hidden="true">

</div><!-- /.modal -->	
