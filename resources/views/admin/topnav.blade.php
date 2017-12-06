
 <style type="text/css">
	   
/*	   .navbar-custom  a  {
 	color:#000;
	background-color: ##777;

}		
.navbar-custom:hover  a {
	color:#000;
	background-color: ##777;
}
*/


.dropdown ul.dropdown-menu {
    width:310px;
}

.ellipsis {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

		
	    </style>

		{!! HTML::style('css/chosen.min.css') !!}

<script>
	$(document).ready(function() {
		$(".users").chosen({no_results_text: "Oops, nothing found!"}); 
	});
	</script>

  <!-- header actions -->
          <div class="header-actions pull-left">

           <!-- (recomended: dont change the id value) -->
           @if(Session::get('hide-options')=='hide')
                    <button id="toggle-content" class="btn btn-icon" type="button"><i class="icon ion-navicon-round"></i></button>
           @else
           			<button id="toggle-content" class="btn btn-icon" type="button"><i class="icon ion-navicon-round"></i></button>
           @endif
                    <!-- (recomended: dont change the id value) -->
                    <!-- <button id="toggle-search" class="btn btn-icon" type="button"><i class="icon ion-search"></i></button> -->
           </div>  
			
		<div class="navbar navbar-default" >
		<div class="navbar-header">
		    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
		      <span class="icon-bar"></span>
		      <span class="icon-bar"></span>
		      <span class="icon-bar"></span>
		    </button>
		  </div>
              <div class="navbar-collapse collapse">
	          <ul class="nav navbar-nav navbar-left" style="text-align:left">
	            <li class="side-nav-item {!!$home_active!!}"><a href="{{ URL::to('admin') }}">Home</a></li>
	            <li class="dropdown side-nav-item {!!$programs_active!!} "><a href="#" class="dropdown-toggle" data-toggle="dropdown"> Programs </strong><span class="icon ion-arrow-down-b"></span></a>
	            	{!! $programs !!}
	            </li>
	            <li class="dropdown side-nav-item {!!$donors_active!!}"><a href="#" class="dropdown-toggle" data-toggle="dropdown"> Donors <span class="icon ion-arrow-down-b"></span></b></a>
		            <ul class="dropdown-menu">
		            <?php $count=0; 
		            $visible_count=0;?>
					@foreach ($donorforms as $donorform)

						<?php 
						$pre_active='';
						$post_active='';
						$add_pre_active='';
						$add_post_active='';
						$d = 'donor-'.$donorform->id.''; 
						if($donorform->id==$current_hysform)
						{
							$pre_active='<strong>';
							$post_active='</strong>';
							if($add_donor=='true')
							{
								$add_pre_active='<strong>';
								$add_post_active='</strong>';
								$pre_active='';
								$post_active='';
							}	
						}
						$count++;
						?>
						@if (isset($permissions->$d) && $permissions->$d == 1) 
							@if($count>1)
								<li class="divider"></li>
							@endif
							<?php $visible_count++; ?>
							<li><a href="{!! URL::to('admin/add_donor', array($donorform->id)) !!}">{!!$add_pre_active!!}Add: {!! $donorform->name !!}{{$add_post_active}}</a></li>
							<li><a href="{!! URL::to('admin/show_all_donors', array($donorform->id)) !!}"><span class='badge pull-right'>{!!$donorform->countDonors($donorform->id)!!}</span>{!!$pre_active!!}Show all: {!! $donorform->name !!} {!!$post_active!!}</a></li>
							
						@endif
					@endforeach

					@if(isset($permissions->groups))
						@if($count>0&&$visible_count==0)
							<li><a href="{!!URL::to('admin/view_groups')!!}">No donor forms available, check your permissions.</a></li>
						@elseif($count==0&&$visible_count==0)
							<li><a href="{!!URL::to('admin/create_form/donor')!!}">No donor forms exist, create one here.</a></li>
						@endif
					@else
						@if($visible_count==0)
								<li><a href="#">No donor forms available.</a></li>
						@endif
					@endif
					
		            </ul>
	            </li>
	            <li class="dropdown side-nav-item {!!$sponsorships_active!!} "><a href="#" class="dropdown-toggle" data-toggle="dropdown"> Sponsorships </strong><span class="icon ion-arrow-down-b"></span></a>
	            	{!! $sponsorships !!}
	            </li>
		            @if (Sentry::check())
		            	<li class="side-nav-item"><a href="{!! URL::route('admin.logout') !!}">Logout</a></li>
		            	@if(isset($emulating))
				           <li class="side-nav-item"><em><a class="navbar-brand" href="#">{!!$emulating!!}</a></em></li>
				        @endif
		            @else
			            <li class="side-nav-item"><a href="{{ URL::to('signup') }}">Signup</a></li>
						<li class="side-nav-item"><a href="{{ URL::route('admin.login') }}">Login</a></li>
		            @endif
	          </ul>
	          
	         @if(isset($user_list))
		         {!! Form::open(array('url'=>URL::to('admin/switch_client'),'class'=> 'navbar-form navbar-right', 'role'=> 'select')) !!}
			        <div class="form-group">
			          <select name="users" class="users form-control" data-placeholder="Select One to Add">
								@foreach ($user_list as $user)
									@if($user->id==Sentry::getUser()->id)
										<option value="{!!$user->client_id!!},{!!$user->group_id!!}" selected >{!!$user->first_name!!} {!!$user->last_name!!} {!!$user->email!!} {!!$user->client_name!!}({!!$user->client_id!!}, {!!$user->group_id!!})</option>
									@else
										<option value="{!!$user->client_id!!},{!!$user->group_id!!}" > {!!$user->first_name!!} {!!$user->last_name!!} {!!$user->email!!} {!!$user->client_name!!}({!!$user->client_id!!}, {!!$user->group_id!!})</option>
									@endif
								@endforeach
								</select>
				    {!! Form::submit('Switch', array('class' => 'btn btn-default')) !!}
			        </div>

					{!! Form::close() !!}
			  @endif

	        </div>
              

		          </div>


	
                <!-- your Awesome App title -->
                <!-- <h1 class="content-title">HelpYouSponsor</h1> -->

   