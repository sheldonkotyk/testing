<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Language" content="en" />
        <meta name="msapplication-config" content="none"/>

        <title>
            @section('title')
            HelpYouSponsor
            @show
        </title>

        <!-- CSS are placed here -->
       
        {!! HTML::style('css/styles.css') !!}
        <!-- {!!HTML::style('https://cdnjs.cloudflare.com/ajax/libs/bootswatch/3.3.6/cerulean/bootstrap.min.css')!!}  -->
         {!!HTML::style('css/bootstrap.min.css')!!} 
        <!-- {!! HTML::style('css/bootstrap-theme.min.css') !!} -->
		{!! HTML::style('css/hys-css.css') !!}

	    <style type="text/css">
	      .sidebar-nav {
	        padding: 9px 0;
	      }
	
	      @media (max-width: 980px) {
	        /* Enable use of floated navbar text */
	        .navbar-text.pull-right {
	          float: none;
	          padding-left: 5px;
	          padding-right: 5px;
	        }
	      }
	      body { padding-bottom: 70px; }
	      
			@if (!empty($template->css ))
				{!! $template->css !!}
			@endif

			#return-to-top {
			    position: fixed;
			    bottom: 20px;
			    right: 20px;
			    background: rgb(0, 0, 0);
			    background: rgba(0, 0, 0, 0.7);
			    width: 50px;
			    height: 50px;
			    display: block;
			    text-decoration: none;
			    -webkit-border-radius: 35px;
			    -moz-border-radius: 35px;
			    border-radius: 35px;
			    display: none;
			    -webkit-transition: all 0.3s linear;
			    -moz-transition: all 0.3s ease;
			    -ms-transition: all 0.3s ease;
			    -o-transition: all 0.3s ease;
			    transition: all 0.3s ease;
			}
			#return-to-top i {
			    color: #fff;
			    margin: 0;
			    position: relative;
			    left: 16px;
			    top: 13px;
			    font-size: 19px;
			    -webkit-transition: all 0.3s ease;
			    -moz-transition: all 0.3s ease;
			    -ms-transition: all 0.3s ease;
			    -o-transition: all 0.3s ease;
			    transition: all 0.3s ease;
			}
			#return-to-top:hover {
			    background: rgba(0, 0, 0, 0.9);
			}
			#return-to-top:hover i {
			    color: #fff;
			    top: 5px;
			}
	    </style>
	    		
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	    <!--[if lt IE 9]>
	      {!! HTML::script('js/html5shiv.js') !!}
	      {!! HTML::script('js/respond.min.js') !!}
	    <![endif]-->

	    {!! HTML::script('https://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js') !!}
        {!! HTML::script('js/tempo.js') !!}
        {!! HTML::script('js/alert.js') !!}
        
        <script type="text/javascript">
			@if (!empty($template->js ))
				{!! $template->js !!}
			@endif
        </script>
        
 		@yield('headerscripts')
    </head>

    	<div class="before">
		@if (!empty($template->html ))
			{!! $template->html !!}
		@endif

		@if(empty($template->html))
 		<nav class="navbar navbar-default navbar-fixed-top ">
 		@endif
	    
	    <div class="container">
 		<div class="navbar-header">
 		<?php
 		$redis = RedisL4::connection();
 		if($redis->hget($session_id,'pagination')=='true')
 			$view_all_url= URL::to('frontend/view_pages/'.$client_id.'/'.$program_id.'/'.$session_id);
 		else
 			$view_all_url= URL::to('frontend/view_all/'.$client_id.'/'.$program_id.'/'.$session_id);
 		 ?>
 			@if(!isset($processed))
		          @if(!isset($disable_program_link))
			          @if(!empty($session_program_names[0]))
				            <a class="btn btn-default navbar-btn" href="{!! $view_all_url !!}" title="@foreach($session_program_names as $n) {!!$n!!} @endforeach"> <span class="glyphicon glyphicon-th"></span>
				            {!!$session_program_names[0]!!}@if(count($session_program_names)>1) and {!!count($session_program_names)-1!!} more. @endif @if(!isset($processed))@endif</a>
				      @endif
				  @elseif($disable_program_link!='1')
					  @if(!empty($session_program_names[0]))
					            <a class="btn btn-default navbar-btn" href="{!! $view_all_url !!}" title="@foreach($session_program_names as $n) {!!$n!!} @endforeach"> <span class="glyphicon glyphicon-th"></span>
					            {!!$session_program_names[0]!!}@if(count($session_program_names)>1) and {!!count($session_program_names)-1!!} more. @endif @if(!isset($processed))@endif</a>
					      @endif
		          @endif
		       @else
		       <a class="btn btn-default navbar-btn active" href="#" onclick="scrollToTop();return false"><span class="glyphicon glyphicon-th"></span> {!!$session_program_names[0]!!}@if(count($session_program_names)>1) and {!!count($session_program_names)-1!!} more. @endif</a>
		       @endif
		    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
		      <span class="icon-bar"></span>
		      <span class="icon-bar"></span>
		      <span class="icon-bar"></span>
		    </button>
		  </div>
	        <div class="navbar-collapse collapse">
	          <div class="pull-left">
	@if(isset($prev))
		@if(!empty($prev))
		<div class="btn-group">
	        <a class='btn btn-default navbar-btn' href="{!!URL::to('frontend/view_entity',array($client_id,$program_id,$prev,$session_id))!!}"><span class="glyphicon glyphicon-chevron-left"></span> Prev</a>
	        </div>
	    @else
	        <a class='btn btn-default navbar-btn' href="{!!URL::to('frontend/view_entity',array($client_id,$program_id,$prev,$session_id))!!}" disabled><span class="glyphicon glyphicon-chevron-left"> Prev</a>
	    @endif
    @endif
	          

			</div>
			
			<div class="pull-right">
			 @if ($session_logged_in=='true')
			  		<span class="label label-info"><strong>Logged in</strong></span>
			  @endif
			  @if($session_order=='true'&&!isset($entities)&&is_numeric($program_id))
         	    	<a class="btn btn-default navbar-btn" href="{{ URL::to('frontend/order', array($client_id, $program_id, $session_id)) }}"><span class="glyphicon glyphicon-list"></span> My Order : {!!isset($total) ? $currency_symbol.$total: ''!!}</a> 
         	    @endif	
         	    @if(isset($entities))
         	    	<a class="btn btn-default navbar-btn active " href="#"><span class="glyphicon glyphicon-list"></span> My Order : {!!isset($total) ? $currency_symbol.$total: ''!!}</a> 
         	    @endif
	          @if ($session_logged_in=='false')
	          		
	         	    @if(!isset($login))
						<a class="btn btn-default navbar-btn" href="{{ URL::to('frontend/login', array($client_id, $program_id, $session_id)) }}"><span class="glyphicon glyphicon-log-in"></span> Login</a>
					@else
						<a class="btn btn-default navbar-btn active" href="#"><span class="glyphicon glyphicon-log-in"></span> Login</a>
					@endif
			  @endif
			 
			   @if ($session_logged_in=='true')
				 	<a class="btn btn-default navbar-btn {!!isset($allow_email) ? 'active': ''!!}" href="{{ URL::to('frontend/donor_view', array($client_id, $program_id, $session_id)) }}"><span class="glyphicon glyphicon-user"></span> My Account</a>
			 		<a class="btn btn-default navbar-btn" href="{{ URL::to('frontend/logout', array($client_id, $program_id, $session_id)) }}"><span class="glyphicon glyphicon-log-out"></span> Logout</a>
			  @endif

			  @if(isset($next))
				  @if(!empty($next))
			          <a class='btn btn-default navbar-btn' href="{!!URL::to('frontend/view_entity',array($client_id,$program_id,$next,$session_id))!!}">Next <span class="glyphicon glyphicon-chevron-right"></span></a>
			      @else
			           <a class='btn btn-default navbar-btn`' href="{!!URL::to('frontend/view_entity',array($client_id,$program_id,$next,$session_id))!!}" disabled>Next <span class="glyphicon glyphicon-chevron-right"></a>
			      @endif
		      @endif
	          </div>
	        </div>
	        </div>
	    @if(empty($template->html))
		</nav>
		   <br>
		   <br>
		   <br>
		@endif
		   
		   <br>
		    <div class="container">  
		    @if(isset($session_messages))  
		    	@foreach ($session_messages as $num => $msg)
		    	@if($num=='error')
		    		<div class="alert alert-danger alert-dismissible fade in">{!!$msg!!}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
		    	@else
		    		<div class="alert alert-success alert-dismissible fade in">{!!$msg!!}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
		    	@endif
				@endforeach
			@endif
	        </div>

			<div class="container ">
	            <!-- Content -->
	            @yield('content')
			</div>
	        <!-- Scripts are placed here -->
		<a href="javascript:" id="return-to-top"><i class="glyphicon glyphicon-chevron-up"></i></a>

	        
	        {!! HTML::script('js/bootstrap.min.js') !!}
			@yield('footerscripts')
			<script >
				// ===== Scroll to Top ==== 
				$(window).scroll(function() {
				    if ($(this).scrollTop() >= 50) {        // If page is scrolled more than 50px
				        $('#return-to-top').fadeIn(200);    // Fade in the arrow
				    } else {
				        $('#return-to-top').fadeOut(200);   // Else fade out the arrow
				    }
				});
				$('#return-to-top').click(function() {      // When arrow is clicked
				    $('body,html').animate({
				        scrollTop : 0                       // Scroll to top of body
				    }, 500);
				});
			</script>
</html>