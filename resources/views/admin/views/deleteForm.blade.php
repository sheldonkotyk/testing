<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title">Delete Form</h4>
</div>
<div class="modal-body">
	<p>Deleting a form makes the field and data saved in the field in any profile inaccessible. The form will be archived and may be restored if needed. 
	{!! Form::open() !!}
	{!! Form::hidden('id', $id) !!}
  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
  	{!! Form::submit('Delete', array('class' => 'btn btn-warning')) !!}
	{!! Form::close() !!}
</div>
