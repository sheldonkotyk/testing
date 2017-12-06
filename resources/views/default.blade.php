<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="msapplication-config" content="none"/>

        <title>
            @section('title')
            HelpYouSponsor
            @show
        </title>

        <!-- CSS are placed here -->
        {!! HTML::style('css/jplist.min.css') !!}
        {!! HTML::style('css/styles.css') !!}
        {!! HTML::style('css/bootstrap.css') !!}
        
	    <style type="text/css">
	      body {
	        padding-top: 75px;
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

        {!! HTML::style('css/bootstrap-theme.min.css') !!}
		{!! HTML::style('css/app-css.css') !!} 
		
		
		
		
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	    <!--[if lt IE 9]>
	      {!! HTML::script('js/html5shiv.js') !!}
	      {!! HTML::script('js/respond.min.js') !!}
	    <![endif]-->
	    {!! HTML::script('js/jquery-2.0.3.min.js') !!}
        {!! HTML::script('js/tempo.js') !!}
        {!! HTML::script('js/jplist.min.js') !!}
 		@yield('headerscripts')
    </head>

    <body>
		<!-- Fixed navbar -->
	    <div class="navbar navbar-inverse navbar-fixed-top">
	      <div class="container">
	        <div class="navbar-header">
	          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	            <span class="icon-bar"></span>
	          </button>
	          <a class="navbar-brand" href="#">{!! HTML::image("img/HYSapplogo.png", "HelpYouSponsor") !!}</a>
	        </div>
	        <div class="navbar-collapse collapse">
	          <ul class="nav navbar-nav">
	            <li><a href="http://helpyousponsor.com">Home</a></li>
	            @if (Sentry::check())
	            <li><a href="{!! URL::route('admin.logout') !!}">Logout</a></li>
	            @else
	            <li><a href="{{ URL::to('signup') }}">Signup</a></li>
				<li><a href="{{ URL::route('admin.login') }}">Login</a></li>
	            @endif
	          </ul>
	        </div><!--/.nav-collapse -->
	      </div>
	    </div>
	   
	
	    <div class="container">    

            <!-- Content -->
            @yield('content')

        </div>

        <!-- Scripts are placed here -->
        
        {!! HTML::script('js/bootstrap.min.js') !!}
	@yield('footerscripts')
    </body>
</html>