<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title">Edit Field</h4>
</div>
<div class="modal-body">

{!! Form::model($file, array('files' => true)) !!}
    <div class="form-group">
        {!! Form::label('file_name', 'File Name') !!}
        <div class="input-group">
            {!! Form::text('file_name', $value = null, array('class' => 'form-control')) !!}
        </div>
    </div>
    
    <div class="form-group">
        {!! Form::label('type', 'File Type') !!}
        <div class="input-group">
            {!! Form::select('type', array('doc' => 'Document', 'image' => 'Image'), null, array('class' => 'form-control')) !!}
        </div>
    </div>
    		    
    <div class="form-group">
        {!! Form::label('permissions', 'Permissions') !!}
        <div class="input-group">
            {!! Form::select('permissions', array('public' => 'Everyone', 'donor' => 'Donor and Admins', 'admin' => 'Admins Only'), null, array('class' => 'form-control')) !!}
        </div>
    </div>
    
    <div class="form-group">
        {!! Form::label('file', 'Replace File') !!}
        <div class="input-group">
        	{!! Form::file('file') !!}
        </div>
    </div>
    
  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
{!! Form::submit('Save Changes', array('class' => 'btn btn-primary')) !!}
{!! Form::close() !!}
</div>