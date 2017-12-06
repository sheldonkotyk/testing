<!DOCTYPE html>
<html lang="en-US">
	<head>
		<meta charset="utf-8">
	</head>
	<body>
	
	<h2>{!! $hysform->name !!}</h2>
		<dl class="dl-horizontal">
			<dt>Created On:</dt>
			<dd>{!! $form->created_at !!}</dd>
			
			@if ($form->created_at != $form->updated_at)
				<dt>Updated On:</dt>
				<dd>{!! $form->updated_at !!}</dd>
			@endif
			
			<dt>By:</dt> 
			<dd>{!! $admin->first_name !!} {!! $admin->last_name !!}</dd>
						
			@foreach ($form_info as $fi)
				<dt>{!! $fi['field_label'] !!}:</dt> 
				<dd>{!! $fi['data'] !!}</dd>
			@endforeach
		</dl>
	</body>
</html>