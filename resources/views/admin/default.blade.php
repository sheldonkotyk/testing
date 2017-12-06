<!DOCTYPE html>
<html>
    <head>

        <meta charset="UTF-8" />
	    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/> 
	    <meta name="viewport" content="initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,width=device-width,height=device-height,target-densitydpi=device-dpi,user-scalable=yes" />
        <title>
            @section('title')
            HelpYouSponsor
            @show
        </title>

  
        <!-- CSS are placed here -->
        {!! HTML::style('css/bootstrap.css') !!}

	   <!-- <style type="text/css">
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
	    </style>-->
        <!-- {!! HTML::style('css/bootstrap-theme.min.css') !!} -->
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	    <!--[if lt IE 9]>
	      {!! HTML::script('js/html5shiv.js') !!}
	      {!! HTML::script('js/respond.min.js') !!}
	    <![endif]-->

	          <!-- Links Added for Syrena Theme -->

        	 <!-- fav and touch icons -->
		    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="{!! asset('assets/app/ico/favico-144-precomposed.png') !!}">
		    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="{!! asset('assets/app/ico/favico-114-precomposed.png') !!}">
		    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="{!! asset('assets/app/ico/favico-72-precomposed.png') !!}">
		    <link rel="apple-touch-icon-precomposed" href="{!! asset('assets/app/ico/favico-57-precomposed.png') !!}">

		    <!-- theme fonts -->
		     <!-- {!!HTML::style('http://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300italic,300,400italic,500,500italic,700,700italic,900,900italic')!!}  -->
		     <link href='https://fonts.googleapis.com/css?family=Roboto:400,100,100italic,300italic,300,400italic,500,500italic,700,700italic,900,900italic' rel='stylesheet' type='text/css'>

		    <!-- theme bootstrap stylesheets -->
		    <!-- {!!HTML::style('assets/bootstrap/css/bootstrap.css')!!}  -->
		    <link href="{!! asset('assets/bootstrap/css/bootstrap.css') !!}" rel="stylesheet" />

		    <!-- theme dependencies stylesheets -->
		   	<!-- {!!HTML::style('assets/app/css/dependencies.css')!!}  -->
		   	<link href="{!! asset('assets/app/css/dependencies.css') !!}" rel="stylesheet" />

		    <!-- theme app main.css (this import of all custom css, you can use requirejs for optimizeCss or grunt to optimize them all) -->
		    <!-- {!!HTML::style('assets/app/css/dependencies.css')!!}  -->
		    <link href="{!! asset('assets/app/css/syrena-admin.css') !!}" rel="stylesheet" />

	    <!-- {!! HTML::script('js/jquery-2.0.3.min.js') !!} -->
		<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>

	    
	    @yield('headerscripts')
	    {!! HTML::style('css/hys-css.css') !!}
    </head>

    <body>
			
	     <section id="wrapper" class="container">   
	   	 	<aside class="side-left"> 
				@include('admin.sidenav')
			</aside>
					
			<section id="content" class="content">
				<header class="content-header">
					@include('admin.topnav')
				</header>
		            <!-- Content -->
	             <!-- define content row -->
            	<div class="content-spliter">
                	<section id="content-main" class="content-main">
                		<div class="content-app">
                			 <div class="app-body" style="">
	            					@yield('content')
	            			</div>
	            		</div>
	            	</section>
	           </div>
			</section>
        </section>
		
		@yield('modal')
		
        <!-- Scripts are placed here -->
		{!! HTML::script('js/chosen.jquery.min.js') !!}
        {!! HTML::script('js/bootstrap.min.js') !!}
        {!! HTML::script('assets/app/js/main.js') !!}
		{!! HTML::script('assets/app/js/dependencies.js') !!}
        {!! HTML::script('assets/jquery-icheck/jquery.icheck.min.js') !!}
        {!! HTML::script('assets/bootstrap-daterangepicker/daterangepicker.js') !!}
        {!! HTML::script('assets/morris/morris.min.js') !!}
        {!! HTML::script('assets/jquery-tags-input/jquery.tagsinput.min.js') !!}
        {!! HTML::script('assets/select2/select2.min.js') !!}
        {!! HTML::script('assets/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js') !!}

		@yield('footerscripts')
    </body>
</html>