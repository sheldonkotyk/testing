	@section('headerscripts')
{!! HTML::style('css/jquery-ui.min.css') !!}
  {!! HTML::script('js/jquery-ui-1.10.3.custom.min.js') !!} 
  
@stop
	

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Edit Commitment 
      <small><em><a href="{!! URL::to('admin/send_new_donor_email', array($commitment->id)) !!}"> Resend Signup Email to {!!$the_donor['name']!!} <span class="glyphicon glyphicon-envelope"></span></a></em></small>
        </h4>
      </div>
      <div class="modal-body">
        {!! Form::model($commitment) !!}
        
        <div class="form-group">
        	{!! Form::label('frequency', 'Payment Frequency') !!}
			{!! Form::select('frequency', $dntns->getFrequencies(), null, array('class' => 'form-control')) !!}
        </div>
        
        <div class="form-group">
        	{!! Form::label('until', 'Sponsorship Ends') !!}
        	{!! Form::text('until', $value = null, array('class' => 'form-control datepicker')) !!}
        </div>

        <div class="form-group">
          {!! Form::label('next', 'Next Action Date') !!}
          {!! Form::text('next', null, array('placeholder'=> 'Format Date YY-MM-DD','class' => 'form-control datepicker')) !!}
          <p class='help-block'>{!!$next!!}</p>
        </div>
        
        <?php
        //if the current method is not in the list, add it!
        $the_methods=$dntns->getMethods();
        if(!in_array($commitment->method,$the_methods))
          $the_methods[$commitment->method]=$dntns->getMethod($commitment->method);
        ?>

        <div class="form-group">
          {!! Form::label('method', 'Payment Method') !!}
			    {!! Form::select('method', $the_methods, null, array('class' => 'form-control','id'=>'modal_method')) !!}
        </div>

        @if($commitment->method=='5')
          <div id="arb_subscription_id" class="form-group">
            {!! Form::label('arb_subscription_id', 'ARB Subscription ID') !!}
            {!! Form::text('arb_subscription_id', null, array('class' => 'form-control')) !!}
          </div>
        @else
          <div id="arb_subscription_id" class="form-group"></div>
        @endif

        <div class="form-group">
        	{!! Form::label('amount', 'Monthly Commitment Amount') !!}
        	{!! Form::text('amount', sprintf("%01.2f", $commitment->amount), array('class' => 'form-control')) !!}
        </div>

        @if(count($programs)>1)
          <div class="form-group">
            {!! Form::label('program_id', 'Program Assignment') !!}
            {!! Form::select('program_id', $programs, $donor_entity->program_id , array('class' => 'form-control')) !!}
          </div>
        @endif
        
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
		{!! Form::submit('Save Changes', array('class' => 'btn btn-primary')) !!}
		{!! Form::close() !!}

		
		@if ($commitment->type == 2)
		</br><p><a href="{!! URL::to('admin/remove_commitment', array($commitment->id)) !!}" class="btn btn-danger">Remove Commitment</a></p>
		@endif
		
		@if ($commitment->type == 1)
      	<p class="text-center">* To remove this commitment you must remove the sponsorship which will automatically remove the commitment.</p>
      	@endif

      </div>

<script>
  $(document).ready(function() {
    $( ".datepicker" ).datepicker({ 
        constrainInput: true,
        changeYear:true, 
        altField: 'input#dateExpire', 
        dateFormat: "yy-mm-dd" ,
        minDate: new Date()
        });  

    $('#modal_method').change(function() {
        var selected = $(this).val();
        
        console.log(selected);
        if ( selected == 5 ) {
          $('div#arb_subscription_id').html('{!! Form::label('arb_subscription_id', 'ARB Subscription ID') !!}
            {!! Form::text('arb_subscription_id', null, $attributes = array('class' => 'form-control')) !!}');
        } else {
          $('div#arb_subscription_id').html('');

        }
      });
  });
  </script>
