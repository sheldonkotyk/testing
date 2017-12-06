<div class="panel-heading">
	            <div class="panel-icon"><i class="icon ion-images"></i></div>
	            <div class="panel-actions">
                    	<div class="badge">{!!count($files)!!} Files</div>
                </div>
	                <h3 class="panel-title">Manage Files</h3>
	            </div><!-- /panel-heading -->
	            <div class="panel-body">

	<h4>Instructions</h4>
	<ul>
		<li>Click the edit button to rotate, delete or set as profile.</li>
		@if($useBox)
			<li>Note: Rotate will not rotate full size files stored on Box.com</li>
		@endif
		<!-- <li>You may change and image file to a document type to handle it as a document in the software.</li> -->
		<li>The green background denotes an image set as the profile.</li>
		<!-- <li>Deleted files are archived and may be restored.</li> -->
		<li>Click the file-thumbnail to download it.</li>
	</ul>

<table class="table table-striped table-condensed table-bordered table-responsive">
	<tr>
		<th>Manage</th>
		<th>File</th>
		@if($box_exists)
			<th>Full Size link</th>
		@endif
		<th>Type</th>
		<th>Permissions</th>
		<th>Date Created</th>
		<th>Date Updated</th>
	</tr>
@if (!empty($files))
@foreach ($files as $file)
	@if ($file['profile'] == 1)
	<tr class="success">
	@else
	<tr>
	@endif

	<td style="min-width:85px">
			<div class="btn-group btn-group-sm">
			  <a data-toggle="modal" href="{!! URL::to('admin/edit_file', array($file['id'])) !!}" data-target="#modal" title="Edit" class="btn btn-default">Edit</a>
			  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
			    <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu" role="menu">
			  @if ($file['type'] == 'Image')
			    <li><a href="{!! URL::to('admin/rotate_pic', array($file['id'])) !!}"><span class="glyphicon glyphicon-retweet"></span> Rotate</a></li>
			    
			    @if ($file['profile'] == 1)
			    	<li><a href="{!! URL::to('admin/make_profile', array($file['id'])) !!}"><span class="glyphicon glyphicon-user"></span> Unset profile</a></li>
			    @else
			    	<li><a href="{!! URL::to('admin/make_profile', array($file['id'])) !!}"><span class="glyphicon glyphicon-user"></span> Make profile</a></li>
			    @endif
			    <!-- @if($file['entity_type']=='entity')
			   		<li><a href="{!! URL::to('admin/make_placeholder', array($file['id'])) !!}"><span class="glyphicon glyphicon-picture"></span> Make Placeholder</a></li>
			  	@endif -->
			  @endif

			  	@if($type=='entity'&&$n>0)
			  		@if($n==1)
			  			<li><a href="{!! URL::to('admin/send_message', array($id,reset($sponsors)['id'],'entity',$file['id'])) !!}"><span class="glyphicon glyphicon-envelope"></span> Send to Donor: "{!!reset($sponsors)['name']!!}"</a></li>
			  		@else	
			  	 		<li><a href="{!! URL::to('admin/send_message', array($id,'all','entity',$file['id'])) !!}"><span class="glyphicon glyphicon-envelope"></span> Send to {!!$n!!} Donor{!!$s!!}</a></li>
			  	 	@endif
			  	@endif
			  	@if($type=='donor'&&$n>0)
			  		@if($n==1)
			  			<li><a href="{!! URL::to('admin/send_message', array(reset($entities)['id'],$id,'donor',$file['id'])) !!}"><span class="glyphicon glyphicon-envelope"></span> Send to Recipient: "{!!reset($entities)['name']!!}"</a></li>
			  		@else	
			  	 		<li><a href="{!! URL::to('admin/send_message', array('all',$id,'donor',$file['id'])) !!}"><span class="glyphicon glyphicon-envelope"></span> Send to {!!$n!!} Recipients</a></li>
			  	 	@endif
			  	@endif

			    <li><a href="{!! URL::to('admin/delete_file', array($file['id'])) !!}"><span class="glyphicon glyphicon-remove"></span> Delete</a></li>
			  </ul>
			</div>				
		</td>
	@if ($file['type'] == 'Image')
		@if(!empty($file['link']))
		<td>
			@if(!empty($file['thumb_link']))
			<a href="{!! $file['link'] !!}"><img src="{!! $file['thumb_link'] !!}" class="img-rounded" style="width:50px" /></a>
			@else
				Thumbnail Processing ...
			@endif
			<a href="{!! $file['link'] !!}"><small>{!! $file['file_name'] !!}</small></a>
		</td>
		@else
			<td>No File</td>
		@endif
	@endif
	@if ($file['type'] == 'Document')
		@if(!empty($file['link']))
			<td>
			<a href="{!! $file['link'] !!}" class="btn btn-primary"><span class="glyphicon glyphicon-file"></span> {!! $file['file_name'] !!}</a>
			</td>
		@else
			<td>No File</td>
		@endif

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
		
	</tr>
@endforeach
@else
<tr><td rowspan="6">No Files to Display.</td></tr>
@endif
</table>
<br/>
<br/>
<br/>
