@extends('frontend.default')

@section('content')





<div style="width: 40%;" class="pull-right">		
<img src="{!! $profilePic !!}" class="img-rounded img-responsive"/>
@if(!empty($image_links))
Images:<br/>
@foreach($image_links as $k => $link)
	<span><a href="{!!$link['original']!!}"><img src="{!!$link['thumbnail']!!}" width='100'></a></span>
@endforeach
<br/>
@endif

@if(!empty($file_links))
Files:
@foreach ($file_links as $k => $link)

	<br/><span><a href="{!!$link['file_link']!!}">{!!$link['file_name']!!}</a></span>
@endforeach
@endif

</div>

<div class="pull-left" style="width: 40%;">
<div class="panel panel-primary"  >
            <div class="panel-body">
	@foreach ($donor_fields as $field)
	
		
				<p>{!!$field->field_label!!}
				
				
 			 <span class="pull-right">
 			 	@if (isset($profile[$field->field_key]))
				
					{!!$profile[$field->field_key]!!}
				@endif
				</span></p>
 		
@endforeach
     </div>
        </div>
	

	</div>

@stop
@section('footerscripts')
<script>
$(document).ready(function() {
        $('.hysTextarea').redactor();
        $("form").validate();
    
});
</script>
@stop