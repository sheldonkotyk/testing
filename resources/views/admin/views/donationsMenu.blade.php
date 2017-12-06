<?php

$the_uri= Request::segments();
        $page= $the_uri[1];

$add_active='';
$add_url=URL::to('admin/add_emailset');

$view_active='';
$view_url=URL::to('admin/view_donations/1?date_from='.Carbon::now()->subDays('30')->format('Y-m-d'));

$edit_active='';
if(isset($emailset))
	$edit_url=URL::to('admin/edit_emailset',array($emailset->id));
else
	$edit_url='#';

$select_active='';
$select_url=URL::to('admin/donations');

if($page=='view_donations')
{
	$view_active='active';
	$view_url='#';
}

if($page=='donations')
{
	$select_url='#';
	$select_active='active';
}
if($page=='add_emailset')
{
	$add_active='active';
	$add_url='#';
}

if($page=='edit_emailset')
{
	$edit_active='active';
	$edit_url='#';
}


?>


<ul class="nav nav-pills">
		

		<li><div class="btn-group"><a href="{!!$select_url!!}"> 
		    <button type="button" class="btn btn-default {!!$select_active!!}">
		       <span class="glyphicon glyphicon-search"></span> Select Donations
		    </button></a></div></li>

		<li><div class="btn-group"><a href="{!!$view_url!!}"> 
		    <button type="button" class="btn btn-default {!!$view_active!!}">
		       <span class="icon ion-ios7-calculator"></span> View {!!($all=='1' ? 'All' : '')!!} Donations
		    </button></a></div></li>

		@if(isset($date_from)&&isset($date_to))
	        <li><div class="btn-group"><button class="btn btn-flat btn-default" id="dashboard-range">
	                <span class="icon ion-ios7-calendar-outline"></span>
	                <span class="text-date">{!! Carbon::createFromTimeStamp(strtotime($date_from))->toFormattedDateString() !!} - {!! Carbon::createFromTimeStamp(strtotime($date_to))->toFormattedDateString() !!}</span> 
	                <span class="icon ion-arrow-down-b"></span>
	            </button></div></li>
        @endif

	     <li class="pull-right"><div class="btn-group"><a data-toggle="collapse" href="#collapseOne">
	            <button type="button" class="btn btn-default">
	               <span class="glyphicon glyphicon-question-sign"></span> About Donations
	            </button></a></div></li>
	     

				<li class="pull-right">
				<br>
				<div id="collapseOne" class="panel panel-default panel-collapse collapse">
				<div class="panel-heading">
	                <div class="panel-icon"><i class="glyphicon glyphicon-question-sign"></i></div>
	               	<div class="panel-actions">
	                    	<div class="label label-success">Info</div>
	                </div>
	                <h3 class="panel-title">About Donations</h3>
	            </div><!-- /panel-heading -->
		              <div class="panel-body">
		              
		              	The Donations page lets you view all Donations as both a graph and table.<br>
		              	You may either <a href="{!!$view_url!!}">view all Donations</a>, or <a href="{!!$select_url!!}">select a group of Donors</a> to display.<br>
		              	Once you are viewing Donations, you may simply click on the Date and choose whatever timeframe you desire.


		              </div>
		            </div>
	            </li>
	        </ul>


 <script type="text/javascript">
    $(function () {
        $from= moment('{!!(isset($date_from) ? $date_from : "")!!}');
        $to= moment('{!!(isset($date_to) ? $date_to : "")!!}');
        // date range picker
        $('#dashboard-range').daterangepicker(
            {

            locale: {
            	format: 'YYYY-MM-DD'
        		},
              ranges: {
                 'Today': [moment(), moment()],
                 'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                 'Last 7 Days': [moment().subtract('days', 6), moment()],
                 'Last 30 Days': [moment().subtract('days', 29), moment()],
                 'This Month': [moment().startOf('month'), moment().endOf('month')],
                 'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
              },
              startDate: $from,
              endDate: $to
            },
            function(start, end) {
                $('#dashboard-range .text-date').text(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
                
                post("{!!URL::to('admin/view_donations')!!}",
                	{Donor_Group : "{!! $hysform['id'] !!}",
                	date_from : start.format('YYYY-MM-DD'),
                	date_to : end.format('YYYY-MM-DD'),
                	fields: '{!!json_encode($fields)!!}',
                	all: '{!!$all!!}'});
        });

        function post(path, params, method) {
		    method = method || "post"; // Set method to post by default if not specified.

		    // The rest of this code assumes you are not using a library.
		    // It can be made less wordy if you use one.
		    var form = document.createElement("form");
		    form.setAttribute("method", method);
		    form.setAttribute("action", path);

		    for(var key in params) {
		        if(params.hasOwnProperty(key)) {
		            var hiddenField = document.createElement("input");
		            hiddenField.setAttribute("type", "hidden");
		            hiddenField.setAttribute("name", key);
		            hiddenField.setAttribute("value", params[key]);

		            form.appendChild(hiddenField);
		         }
		    }

		    document.body.appendChild(form);
		    form.submit();
		}
        });
</script>
