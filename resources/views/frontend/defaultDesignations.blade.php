<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Language" content="en" />

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
	      body {
	        padding-top: 60px;
	        padding-bottom: 40px;
	      }
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
	    </style>

       
		
		
		
		
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	    <!--[if lt IE 9]>
	      {!! HTML::script('js/html5shiv.js') !!}
	      {!! HTML::script('js/respond.min.js') !!}
	    <![endif]-->
	    {!! HTML::script('js/jquery-2.0.3.min.js') !!}
        {!! HTML::script('js/tempo.js') !!}
        
 		@yield('headerscripts')
    </head>

    <body>

	        <div class='container'>
	          <div class="pull-left">
	          
			</div>
			
			<div class="pull-right">
	          @if ($session_logged_in=='false')
	          		@if($session_order=='true')
	         	    	<a href="{{ URL::to('frontend/checkout', array($client_id, $program_id, $session_id)) }}">My Order</a> |
	         	    @endif	
			  @endif
			  @if ($session_logged_in=='true')
			  		<a>Logged in as "{!!$session_donor_name!!}"</a>
				 	| <a href="{{ URL::to('frontend/donor_view', array($client_id, $program_id, $session_id)) }}">My Account</a>
			 		| <a href="{{ URL::to('frontend/logout', array($client_id, $program_id, $session_id)) }}">Logout</a>
			  @endif
	          </div>
	        </div>
	   <br/>
	
	    <div class="container">  
	    @if(isset($session_messages))  
	    	@foreach ($session_messages as $msg)
	    	<div class="alert alert-success">{!!$msg!!}</div>
			@endforeach
		@endif
            <!-- Content -->
            @yield('content')

        </div>

        <!-- Scripts are placed here -->
        
        {!! HTML::script('js/bootstrap.min.js') !!}
	@yield('footerscripts')
    </body>
</html>