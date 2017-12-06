


<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title"> <small><span class="glyphicon glyphicon-arrow-down"></span> {!!$hysform->name!!}</small></h4>
  <h4 class="modal-title">Edit Field </h4>
</div>
<div class="modal-body">
{!! Form::model($field) !!}
    <div class="form-group">
        {!! Form::label('field_label', 'Enter Label') !!}
        <div class="input-group">
            {!! Form::text('field_label', $value = null, array('placeholder' => 'Minimum of 3 characters', 'class' => 'form-control')) !!}
            <span class="input-group-addon"><span class="glyphicon glyphicon-tag"></span></span>
        </div>
		{!! $errors->first('label', '<p class="text-danger">:message</p>') !!}
    </div>
    
    <div class="form-group">
        {!! Form::label('field_type', 'Choose Field Type') !!}
        <div class="input-group">
        	{!! Form::select('field_type', $field_types, $value = null , array('class' => 'form-control')) !!}
        </div>
    </div>


    <div id='gateway-notice'>
        @if($gateway&&$hysform->type=='donor')
            @if($field->field_type=='hysGatewayAddress')
                <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the Address</small>
            @elseif($field->field_type=='hysGatewayCity')
                <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the City</small>
            @elseif($field->field_type=='hysGatewayState')
                <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the State</small>
            @elseif($field->field_type=='hysGatewayZipCode')
                <small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the Zip Code</small>
            @endif
        @endif
    </div>
    
    <div class="form-group">
        {!! Form::label('field_data', 'Enter Data for the Field') !!}
        <div class="input-group">
            {!! Form::text('field_data', $value = null, array('placeholder' => 'Enter additional data', 'class' => 'form-control')) !!}
            <span class="input-group-addon"><span class="glyphicon glyphicon-question-sign"></span></span>
        </div>
        <span class="help-block">Please refer to the instructions for what to enter here. Requirements are different depending on the type of field being added.</span>
    </div>
    
    <div class="form-group">
        {!! Form::label('permissions', 'Permissions') !!}
        <div class="input-group">
            {!! Form::select('permissions', array('public' => 'Everyone', 'donor' => 'Donor and Admins', 'admin' => 'Admins Only'), null, array('class' => 'form-control')) !!}
        </div>
    </div>
    
    <div class="form-group">
        {!! Form::label('is_title', 'Title?') !!}
        <div class="input-group">
            {!! Form::checkbox('is_title', '1') !!}
            <span class="help-block">If checked this field will be used as the profile title (or name).</span>
        </div>
        
    </div>
    
    <div class="form-group">
        {!! Form::label('required', 'Required?') !!}
        <div class="input-group">
            {!! Form::checkbox('required', '1') !!}
            <span class="help-block">If checked this field will be required in order to save the profile information.</span>
        </div>
    </div>

    @if($type!='donor')
	     <div class="form-group">
	        {!! Form::label('sortable', 'Sortable?') !!}
	        <div class="input-group">
	            {!! Form::checkbox('sortable', '1') !!}
	            <span class="help-block">If checked the end user will be able to sort with this field.</span>
	        </div>
	    </div>
        <div class="form-group">
            {!! Form::label('filter', 'Filter?') !!}
            <div class="input-group">
                {!! Form::checkbox('filter', '1') !!}
                <span class="help-block">If checked the end user will be able to filter with this field. Only works with 'Select List' field type.</span>
            </div>
        </div>
    @endif
            
  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
{!! Form::submit('Save Changes', array('class' => 'btn btn-primary')) !!}
{!! Form::close() !!}
</div>

<script>
    $(document).ready(function() {

        $('#field_type').change(function() {
            var selected = $(this).val();
            
            if ( selected == 'hysGatewayAddress' ) 
            {
                $('div#gateway-notice').html('<small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the Address</small>');
            }
            else if ( selected == 'hysGatewayCity' ) 
            {
                  $('div#gateway-notice').html('<small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the City</small>');
            }
            else if ( selected == 'hysGatewayState' ) 
            {
                  $('div#gateway-notice').html('<small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the State</small>');
            }
            else if ( selected == 'hysGatewayZipCode' ) 
            {
                  $('div#gateway-notice').html('<small class="text-warning">Note: This field will post to <em>{!!$gateway!!}</em> as the Zip Code</small>');
            }
            else {
                $('div#gateway-notice').html('');
            }
        });

        
       
    });
</script>
