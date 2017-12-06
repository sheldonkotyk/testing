@extends('frontend.default')
 
@section('headerscripts')

    <meta property="og:title" content="See {!!$program_name!!}"/>
    <meta property="og:url" content="{!!URL::to('frontend/view_all',array($client_id,$program_id))!!}"/>
    <meta property="og:image" content="{!!$first_thumb!!}"/>
    <meta property="og:site_name" content="{!!$client->organization!!}"/>
    <meta property="og:description" content="See {!!$program_name!!}"/>

  {!! HTML::style('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/css/bootstrap-select.min.css') !!}
  {!! HTML::script('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.10.0/js/bootstrap-select.min.js') !!}  
	{!! HTML::style('css/jplist.css') !!}
	{!! HTML::script('js/jplist.min.js') !!}
  {!! HTML::script('https://cdnjs.cloudflare.com/ajax/libs/jquery.lazyload/1.9.1/jquery.lazyload.min.js')!!}

@stop

@section('content')
@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

@if(!empty($text_front))
<div class="pull-left panel panel-default" style="width: 100%;">
  <div class="panel-body">
      {!! $text_front !!}
  </div>
</div>
@endif


<!-- demo -->
<div id="demo" class="box jplist grid">
   <!-- top panel -->
   <div class="panel box panel-top">
     
      <!-- sorting -->
      <?php 
      $hidden='hidden'; 
      if($sorting=='1')
      {
          $hidden='';
      }
      ?>

      @if(count($program_names)>1)
      <select class="  select pull-left selectpicker" data-control-type="select" data-control-name="program-filter" data-control-action="filter">
                  <option data-path="default">View All</option>
                 @foreach($program_names as $id => $name)
                 <option data-path=".hysprogram_id{!!$id!!}">{!!$name!!}</option>
                 @endforeach
      </select>
      @endif
      

      <select
         class=" select pull-left {!!$hidden!!} selectpicker" 
         data-control-type="select" 
         data-control-name="sort" 
         data-control-action="sort" 
         data-datetime-format="{month} {day}, {year}">
         
		
		<!-- Puts all the public fields into the sort dropdown -->
		@foreach($sort_fields as $field)


      @if($field->field_type=='hysDate')
        <option data-path=".{!!$field->field_key!!}" data-order="asc" data-type="datetime" data-icon="glyphicon glyphicon-sort-by-attributes-alt">{!! $field->field_label !!}</option></span>
        <option data-path=".{!!$field->field_key!!}" data-order="desc" data-type="datetime" data-icon="glyphicon glyphicon-sort-by-attributes">{!! $field->field_label !!}</option>

        <!-- If the select list is numeric, then allow for numeric sorting -->
      @elseif($field->field_type=='hysSelect'&&is_numeric(implode('',explode(',',$field->field_data))))
        <option data-path=".{!!$field->field_key!!}" data-order="desc" data-type="number" data-icon="glyphicon glyphicon-sort-by-attributes">{!! $field->field_label !!}</option>
        <option data-path=".{!!$field->field_key!!}" data-order="asc" data-type="number" data-icon="glyphicon glyphicon-sort-by-attributes-alt">{!! $field->field_label !!}</option>

      @else

  		  <option data-path=".{!!$field->field_key!!}" data-order="asc" data-type="text" data-icon="glyphicon glyphicon-sort-by-attributes-alt">{!! $field->field_label !!}</option>
        <option data-path=".{!!$field->field_key!!}" data-order="desc" data-type="text" data-icon="glyphicon glyphicon-sort-by-attributes">{!! $field->field_label !!}</option>
	   
     @endif

  	@endforeach
                  
      </select>


      
      
     <!--  Default sort hidden control
      Sorts by default as the first sort field -->
      @if(isset($sort_fields->first()->field_key))
      <div 
         class="hidden " 
         data-control-type="select-sort" 
         data-control-name="sort" 
         data-control-action="sort"
         data-path=".{!! $sort_fields->first()->field_key !!}" 
         data-order="asc" 
         data-type="text">
      </div>   
      @endif

      <!-- filter -->
       <div class="pull-right input-group col-sm-3 col-xs-3 col-md-3 "> 
                        <!--[if IE]><div class="search-title left">Filter by title: </div><![endif]-->
           <span class="input-group-addon"><i  class="glyphicon glyphicon-search"></i></span>
            <input data-path=".search" type="text" value="" placeholder="Search" data-control-type="textbox" data-control-name="search-filter" data-control-action="filter"  class="form-control">             
       </div>

      @foreach($filter_fields as $field)
        @if($field->filter=='1'&&count(explode(',',$field->field_data))>1&&$field->field_type=='hysSelect')
      <select class="select pull-left selectpicker" data-control-type="select" data-control-name="{!!$field->field_key!!}-filter" data-control-action="filter">
                
                  <option data-path="default">Filter by {!!$field->field_label!!}</option>
                 @foreach(explode(',',$field->field_data) as $val)

                 <option data-path=".hysfilter-{!!$field->field_key!!}{{str_replace(' ', '_' ,$val)}}">{!!$val!!}</option>

                 @endforeach
      </select>
        @endif
      @endforeach

      
      </div>
      
   <!-- data -->   
   <div class="grid list box text-shadow">

    @foreach($processed as $k=> $profile)
      <div class="list-item box">
      
         <!-- profile image -->
            <a class="img" href="{!! $profile['url'].'/'.$session_id !!}">
              <img data-original="{!!$profile['file_link']!!}" alt="" title=""  style="width:300px;"  class="img-thumbnail lazy" >
            </a>

         <!-- data -->
         <div class="block" style="height:{!!$height!!}px; overflow:hidden;">
            
           <!--  Display Recipient Title -->
            <a href="{!! $profile['url'].'/'.$session_id  !!}">
            {!! $profile['title_fields'] !!}
            </a>

            {!! $profile['entity_info'] !!}

            {!!$profile['entity_percent_display']!!}

      			<span class="search hidden">
      				   {!! $profile['search_fields'] !!}
                 {!!$program_names[$profile['hysprogram_id']]!!}
            </span>
            
            <!-- This allows for sorting by program when many programs are displayed -->
            {!!$profile['program_sort']!!}

            {!!$profile['sort_fields']!!}

            {!!$profile['filter_fields']!!}

            </div>
      </div>
      @endforeach
      
   </div>
   
   <div class="box jplist-no-results text-shadow align-center">
      <p>No results found</p>
   </div>

     
   </div>

</div>
<!-- end of demo -->
<script type='text/javascript'> 
   $('document').ready(function(){

      
      $('#demo').jplist({ 
         items_box: '.list', 
         item_path: '.list-item',
         panel_path: '.panel',
         
         cookies: false,
         cookie_name: 'jplist-list-grid',
         redrawCallback: 'loadem',
         control_types: {    
             'drop-down':{
               class_name: 'Dropdown'
               ,options: {}
            }
          ,'placeholder':{
               class_name: 'Placeholder'
               ,options: {
                  //paging
                  
                  //arrows
                  prev_arrow: '<button type="button" class="btn btn-default"> <span class="glyphicon glyphicon-chevron-left"></span> Prev Page </button>'
                  ,next_arrow: '<button type="button" class="btn btn-default"> Next Page <span class="glyphicon glyphicon-chevron-right"></span> </button>'
                  ,first_arrow: ''
                  ,last_arrow: ''
               }
            }
            }  
      });

     
   
      $("img.lazy").lazyload({
        event: 'sporty',
      }); 

    $(window).bind("load", function() {
        var timeout = setTimeout(function() {
            $("img.lazy").trigger("sporty")
        }, 1);
    });

   });
</script>           


@stop