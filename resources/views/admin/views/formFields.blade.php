<p><strong>Select fields to display</strong></p>
@foreach ($fields as $f)
<div class="checkbox">
	<label> {!! Form::checkbox('fields[]', $f['field_key'], $value = null, array()) !!} {!! $f['field_label'] !!} </label> 
</div>
@endforeach