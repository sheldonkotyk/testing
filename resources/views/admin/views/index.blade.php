@extends('admin.default')

@section('headerscripts')
  {!! HTML::style('css/demo_table.css') !!}
  {!! HTML::script('media/js/ZeroClipboard.js') !!}
  {!! HTML::style('DataTables-1.10.4/media/css/jquery.dataTables.css') !!}
  {!! HTML::style('DataTables-1.10.4/extensions/TableTools/css/dataTables.tableTools.css') !!}
  {!! HTML::script('DataTables-1.10.4/media/js/jquery.dataTables.js') !!}
  {!! HTML::script('DataTables-1.10.4/extensions/TableTools/js/dataTables.tableTools.js') !!}
  
@stop

@section('content')

@if (Session::get('message'))
	<div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

@if (isset($permissions->group_all) && $permissions->group_all == 1)

@if(!isset($stats))

    <div class="panel panel-default magic-element">
      <!-- Default panel contents -->
      <div class="panel-body-heading">
       <h2 class="pb-title">
     Loading Dashboard <span class="pull-right"></span>
      </h2>
      </div>
      <div class="panel-body">
      <strong>The Dashboard Statistics are currently being reloaded. Come back to this page in a few minutes to see them.</strong>
        
      </div>
    </div>
    @else

<div class="magic-layout" data-cols="4">
      @if(isset($permissions->donations))
      <div class="panel panel-default magic-element">
          <a href="{!!URL::to('admin/view_donations/1/')!!}?date_from={!!Carbon::now()->subMonth()->format('Y-m-d')!!}" class="panel-body-heading full-line">
              <div class="pb-object pb-object-circle">
                  <i class="pbo-icon icon ion-ios7-plus-empty"></i>
              </div><!-- /pb-object -->
              <h3 class="pb-title ">
                  <span class="text-info">{!!$stats['# of Donations In Last 30 Days']!!}</span> <small> # of Donations <br/> in Last 30 Days</small> 
              </h3><!-- /pb-title -->
              <span class="pb-watermark">
                  <i class="icon ion-ios7-personadd-outline"></i>
              </span><!-- /pb-watermark -->
          </a><!-- /panel-body-heading -->
      </div><!-- /panel -->
      @endif

      <div class="panel panel-default magic-element">
          <a href="#" class="panel-body-heading full-line">
              <div class="pb-object pb-object-circle">
                  <i class="pbo-icon icon ion-arrow-graph-up-right"></i>
              </div><!-- /pb-object -->
              <h3 class="pb-title ">
                  <span class="text-warning">{!!$stats['New Commitments In Last 30 Days']!!}</span> <small>New Sponsorships <br/>in Last 30 Days</small>
              </h3><!-- /pb-title -->
              <span class="pb-watermark">
                 <i class="icon ion-ios7-personadd-outline"></i>
              </span><!-- /pb-watermark -->
          </a><!-- /panel-body-heading -->
      </div><!-- /panel -->

       @if(isset($permissions->donations))
       <div class="panel panel-default magic-element">
          <a href="{!!URL::to('admin/view_donations/1/')!!}?date_from={!!Carbon::now()->subMonth()->format('Y-m-d')!!}" class="panel-body-heading full-line">
              <div class="pb-object pb-object-circle">
                  <i class="pbo-icon icon ion-ios7-arrow-thin-up"></i>
              </div><!-- /pb-object -->
              <h3 class="pb-title ">
                  <span class="text-success">${!!$stats['Total Donations Made In Last 30 Days']!!}</span> <small>Total Donations <br/> In Last 30 Days</small>
              </h3><!-- /pb-title -->
              <span class="pb-watermark">
                  <i class="icon ion-ios7-star-outline"></i>
              </span><!-- /pb-watermark -->
          </a><!-- /panel-body-heading -->
      </div><!-- /panel -->
      @endif

      <div class="panel panel-default magic-element">
        @if(isset($permissions->{'program-all'})&&$permissions->{'program-all'}=='1')
          <a href="{!!URL::to('admin/show_all_sponsorships/all')!!}" class="panel-body-heading full-line">
        @else
          <a href="#" class="panel-body-heading full-line">
        @endif

              <div class="pb-object pb-object-circle">
                  <i class="pbo-icon icon ion-ios7-pricetag-outline"></i>
              </div><!-- /pb-object -->
              <h3 class="pb-title">
                  <span class="text-danger">{!! $stats['Total Number of Commitments']!!}</span> <small>Total Number <br/> of Sponsorships</small>
              </h3><!-- /pb-title -->
              <span class="pb-watermark">
                  <i class="icon ion-ios7-people-outline"></i>
              </span><!-- /pb-watermark -->
            </a><!-- /panel-body-heading -->
      </div><!-- /panel -->
  </div><!-- /magic-layout -->

    <!-- <h1>{!! $org->organization !!} Dashboard</h1> -->

  <div class="magic-layout">


  @if(isset($permissions->donations))

      @if(!empty($donation_graph_data))
      <div class="panel panel-default magic-element">
            <div class="panel-body-heading">
                <h3 class="pb-title">
                    $ 
                     <a href="{!!URL::to('admin/donations')!!}">Donations</a> last 
                    <a href="{!!URL::to('admin/view_donations/1/')!!}?date_from={!!Carbon::now()->subMonth()->format('Y-m-d')!!}">30</a> days
                      (<a href="{!!URL::to('admin/view_donations/1/')!!}?date_from={!!Carbon::now()->subDays(90)->format('Y-m-d')!!}">90</a>,
                      <a href="{!!URL::to('admin/view_donations/1/')!!}?date_from={!!Carbon::now()->subYear()->format('Y-m-d')!!}">Year</a>)
                </h3><!-- /pb-title -->
            </div><!-- /panel-body-heading -->
            <div class="kits-chart">
                <div id="donation-chart" class="chart"></div>
            </div><!-- /kits-chart -->
        </div><!-- /panel -->
      @endif
    @endif

    @if(!empty($commitment_graph_data))
     <div class="panel panel-default magic-element">
          <div class="panel-body-heading">
              <h3 class="pb-title">
                  New Sponsorships (last 30 days)
              </h3><!-- /pb-title -->
          </div><!-- /panel-body-heading -->
          <div class="kits-chart">
              <div id="commitment-chart" class="chart"></div>
          </div><!-- /kits-chart -->
      </div><!-- /panel -->
    @endif

    
    


  <div class="panel panel-default magic-element">
    <!-- Default panel contents -->
    <div class="panel-body-heading">
    <h3 class="pb-title">
    Stats <span class="pull-right"><small>Last Updated: {!!$date!!}</small></span>
    </h3>

    </div>
    <div class="panel-body">
      @if(empty($reloaded))
        <span class="pull-left"><a href="{!!URL::to('admin/reload_stats')!!}" class="btn btn-sm btn-primary"><strong>Reload Stats</strong></a></span>
      @else
        <span class="pull-left"><a href="{!!URL::to('admin')!!}" class="btn btn-sm btn-primary"><strong>{!!$reloaded!!}</strong></a></span>
      @endif
      <table id="stats_table" class="table table-bordered">
      <thead>
      <tr>
        <th>Name</th>
        <th>Number</th>
      </tr>
      </thead>
        <tbody>
            @foreach ($stats as $name => $stat)
              <tr>
               <td>{!!$name!!}</td><td>{!!$stat!!}</td>
              </tr>
            @endforeach
          </tbody>
          </table>
          </div>
        <div class="panel-body-heading">
          <h3 class="pb-title">
          Sponsorships by program
          </h3>
          </div>
    <div class="panel-body">
          <table id="commitments_table" class="table table-bordered">
          <thead>
          <tr>
            <th>Program</th>
            <th># of Sponsorships</th>
            <th>Pledged revenue per month</th>
          </tr>
          </thead>
          <tbody>
            

             @foreach($commitments_by_program as $program_num => $comm)
                @foreach($comm as $name => $data)
                   <tr>
                   <td><a href="{!!URL::to('admin/show_all_sponsorships/'.$program_num)!!}">{!!$name!!}</a></td> <td>{!!$data['total']!!}</td><td>{!!number_format($data['amount'],0,'.','')!!}</td>
                  </tr>
                @endforeach
             @endforeach
       </tbody>
       </table>
       </div>
       @if(!empty($remainders_by_program))
          <div class="panel-body-heading">
            <h3 class="pb-title">
            Remaining by program 
            </h3>
            This table displays the total remaining commitments that are yet to be filled.
            Only will display statistics for programs of "number" type.
            </div>
      <div class="panel-body">
            <table id="remainders_table" class="table table-bordered">
            <thead>
            <tr>
              <th>Program</th>
              <th>Remaining</th>
            </tr>
            </thead>
            <tbody>
              

               @foreach($remainders_by_program as $program_num => $rem)
                  @foreach($rem as $name => $total)
                     <tr>
                     <td><a href="{!!URL::to('admin/show_all_entities/'.$program_num)!!}">{!!$name!!}</a></td> <td>{!!$total!!}</td>
                    </tr>
                  @endforeach
               @endforeach
         </tbody>
         </table>
         </div>
       @endif
       @if(!empty($funding_by_program)&&isset($permissions->donations))
          <div class="panel-body-heading">
            <h3 class="pb-title">
            Funding Programs Stats
            </h3>
            This table displays the total donations for each Funding type program.
            </div>
      <div class="panel-body">
            <table id="funding_table" class="table table-bordered">
            <thead>
            <tr>
              <th>Program</th>
              <th># of Donations</th>
              <th>Total amount raised</th>
            </tr>
            </thead>
            <tbody>
              

               @foreach($funding_by_program as $program_num => $rem)
                  @foreach($rem as $name => $data)
                     <tr>
                     <td><a href="{!!URL::to('admin/show_all_entities/'.$program_num)!!}">{!!$name!!}</a></td> <td>{!!$data['total']!!}</td><td>{!!number_format($data['amount'],0,'.','')!!}</td>
                    </tr>
                  @endforeach
               @endforeach
         </tbody>
         </table>
         </div>
       @endif


  </div>

    <div class="panel panel-default magic-element">
    <!-- Default panel contents -->
    <div class="panel-body-heading">
     <h2 class="pb-title">
      New HYS Upgrades! <span class="pull-right"> <small> Updated: September 15th 2016 </small> </span>
    </h2>
    </div>

    <div class="panel-body">
      <strong>Infinite Scrolling or Pagination on frontend program view</strong>
      <br>
      Recently we changed the default on the frontend to infinite scrolling for displaying recipients. If you want to change it back to pagination, 
      change your iframe code from "...frontend/view_all.." to "...frontend/view_pages..." <br>
      <br>
      Don't change a thing in your iframe code and you will have infinite scrolling. If you use infinite scrolling, it works better if you add scrolling="yes" in the iframe code or <a href="{!!URL::to('admin/template')!!}">don't use the iframe at all!</a>

    </div>
    <div class="panel-body">


    </div>
  </div>

    <div class="panel panel-default magic-element">
    <!-- Default panel contents -->
    <div class="panel-body-heading">
     <h2 class="pb-title">
      More HYS Upgrades! <span class="pull-right"> <small> Updated: July 11th 2016 </small> </span>
    </h2>
    </div>

    <div class="panel-body">
      <strong>Display Random Recipients</strong>

      Click on your program, then click on "Embed"

      Using the 'Random Single Recipient Link' you can display a new sponsorship recipient every time the page loads.
      <br>
      When people want to sponsor a child, or a project they often don't want to sort through hundreds of options. Using the "Random Single Recipent Link" can help your donor to decide who or what to sponsor.
      <br>
      <br>

      <strong>Prev and Next Buttons added to Recipient profile page.</strong>
      Donors can now view the profile pages of your sponsorship recipients  by clicking "Prev" and "Next" without needing to go back to the "View All" page.
      <br>
      The sorting used by these buttons is by what the recipient system Id number is (ie. the order in which they were added to the program) This sorting cannot be changed.
      <br>
      <br>
      <strong>Frontend User Interface Improvements</strong>

      Have a look at the frontend to see what some of the changes are. Many of the changes are simply stylistic, but you should find that overall it now looks more clean and professional. Also, the responsiveness (ability to deal with different screen sizes and devices) has been drastically improved.

    </div>
    <div class="panel-body">

    <strong>Disable payment schedule options</strong>
    <br><br>
        Under <a href="{!!URL::to('admin/settings')!!}"> Program Options -> Settings </a> you can now keep donors from changing their payment schedule when they sign up. All new sponsorships will default to monthly if you check this option.
        The option looks like this: 
        <div class="checkbox">
                <label>
                  {!! Form::checkbox('hide_frequency', '1') !!}
                  Disable payment schedule options (defaults to Monthly)
                </label>
              </div>

    <br><br>

  <strong>Remove payment information from donor's "My Account" page</strong>
    <br><br>
        If you go to your <a href="{!!URL::to('admin/forms')!!}">Donor Profile</a> and click on "Edit Form Details" You will see this option:
         <div class="checkbox">
            {!!Form::checkbox('hide_payment','1')!!}
            {!! Form::label('hide_payment', 'Remove payment information from donor\'s "My Account" page') !!}
          </div>

          Clicking this will remove payment and donation information from the donor's account page. This is helpful if you use other software to process payments and don't want the donor to feel they are being charged twice. <br>
          Note: If you use use this option, make sure you aren't sending <a href="{!!URL::to('admin/email')!!}">auto-email receipts</a> to the donor.

    <br><br>
  

    <strong>Allow donors to modify payment amount and schedule.</strong>
    <br><br>
      If you go to your <a href="{!!URL::to('admin/forms')!!}">Donor Profile</a> and click on "Edit Form Details" You will see this option:
      <br>
      <div class="checkbox">
            {!!Form::checkbox('can_donor_modify_amount','1')!!}
            {!! Form::label('can_donor_modify_amount', 'Allow donors to modify payment amount and schedule.') !!}
          </div>
          <br>

          Checking this box allows Donors to modify both the amount that they pay as well as their payment schedule. Only use this options if you trust your donors not to abuse this!<br>
          Note: When the Donor changes their payment schedule (say from monthly to yearly), it resets their schedule to begin payments on that day.


      <br><br>
    <strong>Donations Graph</strong>
    <br><br>
    <a href="{!!URL::to('admin/view_donations/1?date_from='.Carbon::now()->subDays('30')->format('Y-m-d'))!!}">Donations Table</a> now displays a graph along with data table!
    

    </div>
  </div>

<div class="panel panel-default magic-element">
    <!-- Default panel contents -->
    <div class="panel-body-heading">
     <h2 class="pb-title">
      HYS Upgrades <span class="pull-right"> <small> Updated: Sept 24rd 2015 </small> </span>
    </h2>
    </div>
    <div class="panel-body">

    <strong> Donor <-> Recipient two-way Messaging! </strong>

    <br>
    After uploading a file, you will now see an option to send files, like this:<br>
    <img src="https://s3-us-west-1.amazonaws.com/hys/3937ff8f88f1892695f5a10db6464dd6ee918cc51.png" style="height:180px; ">

    <br>
    If you upload a file to a Recipient, it can be sent to one or all of the sponsors.
    <br>
    Likewise, if you upload a file to a Donor, you can then send it to one or all of the recipients that belong to the donor.
    <br>
    For security, these are in-system messages, not emails. However you can setup <a href="{!!URL::to('admin/email')!!}">auto-emails</a> to notify Donors and Admins when messages are sent.
    <br><br>

    Also, Donors can now reply to messages from Recipients from their login.
<br><br>
    This addition was made to allow easy scanning and sending of letters to and from sponsorship recipients. But it also works with all kinds of files.


    <strong> Designations Upgrade </strong><br>

    <a href="{!!URL::to('admin/all_designations')!!}">Designations</a> have in the past been tacked onto a program, so when people sponsor a child or project, they can add an extra designation.<br>
    Now, you can also easily embed, email, tweet, or facebook share single designations without having to attach them to a program.<br>
    <br>
    <a href="{!!URL::to('admin/all_designations')!!}">Go to your designations</a>, and try out the new social media and embed buttons!
    <br>
    <br>

    <strong> Progress bar and Sponsorship Stats on Frontend </strong><br>

    <p><span>$715 To Go | 17 sponsors</span></p>
    <div class="progress" style="max-width:90%;text-align:center;margin: 0 auto;">
                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 29%; min-width: 2em;">
                  29%
                </div>
              </div>
    <br>
    Use these two checkboxes <a href="{!!URL::to('admin/settings')!!}"> in the Program Settings</a> to show your users the progress of each recipient.<br>

    <div class="checkbox">
                <label>
                  {!! Form::checkbox('display_percent', '1') !!}
                  Display Progress Bar
                </label>
              </div>
              <div class="checkbox">
                <label>
                  {!! Form::checkbox('display_info', '1') !!}
                  Display Sponsorship Stats
                </label>
              </div>

    <br><br>
    
    <strong>Send Year End Statments from HYS!</strong> <span class="pull-right"><small>Updated: Sept 16th 2015</small></span><br>

    Steps:<br>
    1. Create a <a href="{!!URL::to('admin/email')!!}"> Donor Year End Statement</a> auto email.<br>
    2. Add yourself as a 'Test Donor' with your email address. <br>
    3. Add a test donation for your 'Test Donor' .<br>
    4. From your 'Test Donor' Profile page, use the "Email" Button.<br>
    5. Wait a minute, check your email and view your test year end statement.<br><br>
    <em>If the email looks good, you are ready to send to your Donors!</em><br><br>
    You can send out year end statements three ways: <br>
    <strong>One-at-a-time</strong>: From the Donor Profile page, use the "Email" Button. <br>
    <strong>All</strong>: From the Donors page, use the "Email" Button to send to all donors.<br>
    <strong>Choose</strong>: From the Donors page, click to choose donors, then use the "Email" Button.<br>

    <br><br>

    <strong>Manually Send Notify Donor of Account Setup</strong> <br>
    The "Email" Button found on the "Donors" page and the "Donor Profile" page can also be used to send the "Notify Donor of Account Setup" auto Email.<br>
    If you want to populate your Donors list, and then send the notification emails all at once, now you can!
    <br><br>

    <strong>Mailchimp Auto-Sync Integration</strong><span class="pull-right"><small>Updated: Sept 1st 2015</small></span>
     <a href="https://mailchimp.com" target="_blank"><img class="pull-right" src="{!!URL::to('img/Freddie_wink_1.png')!!}"></a>
    <br><br>
    It's now possible to connect HelpYouSponsor with <a href="https://mailchimp.com" target="_blank">Mailchimp</a>.<br>
    This will automatically sync your donors to a Mailchimp list of your choice.
    <br>

    Step 1: <a href="https://mailchimp.com" target="_blank"> Get yourself a Mailchimp account.</a>
    <br>
    Step 2 : Create a new list in your Mailchimp account.
    <br>
    Step 3: <a href="https://admin.mailchimp.com/account/api-key-popup" target="_blank">Get your Mailchimp API Key.</a>
    <br>
    Step 4: <a href="{!!URL::to('admin/edit_client_account#emailsettings')!!}">go to the Account page</a> and input your Mailchimp API key and click 'Save and Test'
    <br>
    Step 5: <a href="{!!URL::to('admin/forms')!!}">Go to the Forms page</a> and choose your Donor Form, (This will be labeled 'Donor Profile' under the 'Type' column)
    <br>
    Step 6 (optional): On the 'Manage Fields' page you will see the fields for your Donor form. The fields marked as 'title' will be exported to Mailchimp. The first title field will be input as the mailchimp-firstname and any other 'title' fields will be put into the mailchimp-lastname.
    <br>
    Step 7: Click on "Edit Form Details"
    <br>
    Step 8: Select your Mailchimp list and click 'Save'
    <br><br>
    Finished! Now all Donors in that donor-form will be automatically synced to your Mailchimp List.
    </div>



  <a href="{!!URL::to('admin/upgrades')!!}" class="bnt btn-primary"><div class="btn btn-info">View Past Upgrades</div></a>
  <br>
  </div>

</div>
@endif

@else
<h1>{!! $org->organization !!}</h1>
@endif



@stop

@section('footerscripts')
    <!-- jQuery, theme required for theme -->
    <!-- // <script src="/assets/jquery/jquery.js"></script> -->
    <!-- // <script src="/assets/bootstrap/js/bootstrap.min.js"></script> -->
    
    <!-- theme dependencies -->
    <!-- 
        Contents List
        1. Raphaël
        2. Isotope
        3. verge
        4. Moment
        5. Prettify
    -->
        
        <!-- other dependencies -->
    
    <!-- theme app main.js -->
    <script type="text/javascript">
    $(function () {
        
        // date range picker
        $('#dashboard-range').daterangepicker(
            {
              ranges: {
                 'Today': [moment(), moment()],
                 'Yesterday': [moment().subtract('days', 1), moment().subtract('days', 1)],
                 'Last 7 Days': [moment().subtract('days', 6), moment()],
                 'Last 30 Days': [moment().subtract('days', 29), moment()],
                 'This Month': [moment().startOf('month'), moment().endOf('month')],
                 'Last Month': [moment().subtract('month', 1).startOf('month'), moment().subtract('month', 1).endOf('month')]
              },
              startDate: moment().subtract('days', 6),
              endDate: moment()
            },
            function(start, end) {
                $('#dashboard-range .text-date').text(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
            }
        );
      
        
        // charts
        // chart commitments

        @if(!empty($commitment_graph_data))

          var data1 = {!!json_encode($commitment_graph_data)!!}
          ,
          commitmentChart = Morris.Bar({
              element: 'commitment-chart',
              data: data1,
              barColors: ['#3498db'],
              gridTextColor: '#34495e',
              // pointFillColors: ['#3498db'],
              xkey: 'dates',
              ykeys: ['totals'],
              labels: ['New Sponsorships'],
              barRatio: 0.4,
              hideHover: 'auto'
          });

        @endif

        @if(!empty($donation_graph_data))
          var data2= {!!json_encode($donation_graph_data)!!}
          ,
          donationChart = Morris.Bar({
              element: 'donation-chart',
              data: data2,
              barColors: ['#3498db'],
              gridTextColor: '#34495e',
              // pointFillColors: ['#3498db'],
              xkey: 'dates',
              ykeys: ['totals'],
              labels: ['Donations'],
              barRatio: 0.4,
              hideHover: 'auto'
          });
        @endif
     

        // update data on content or window resize
        var update = function(){
            @if(!empty($commitment_graph_data))
              commitmentChart.redraw();
            @endif
          
            @if(!empty($donation_graph_data))
              donationChart.redraw();
            @endif
        }

        // handle chart responsive on toggle .content
        $(window).on('resize', function(){
            update();
        })
        
        $('#toggle-aside').on('click', function(){
            // update chart after transition finished
            $("#content").bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){
                update();
                $(this).unbind();
            });
        })
        $('#toggle-content').on('click', function(){
            update();
        })
        // end chart



        // todo list
        $('.icheck').iCheck({
            checkboxClass: 'icheckbox_flat-green',
            radioClass: 'iradio_flat-green',
            increaseArea: '20%' // optional
        }).on('ifChanged', function(){
            var $this = $(this),
                todo = $(this).parent().parent().parent();

            todo.toggleClass('todo-marked');
            todo.find('.label').toggleClass('label-success');
        });



        // Quick Mail
        $('#quick-mail-reseiver').tagsInput({
            height: '70px',
            width:'auto',           // support percent (90%)
            defaultText: '+ reseiver'
        });
        // manual style for .tagsinput
        $('div.tagsinput input').on('focus', function(){
            var tagsinput = $(this).parent().parent();
            tagsinput.addClass('focus');
        }).on('focusout', function(){
            var tagsinput = $(this).parent().parent();
            tagsinput.removeClass('focus');
        });
        $('#quick-mail-content').wysihtml5({
            "font-styles": true, //Font styling, e.g. h1, h2, etc. Default true
            "emphasis": true, //Italics, bold, etc. Default true
            "lists": false, //(Un)ordered lists, e.g. Bullets, Numbers. Default true
            "html": false, //Button which allows you to edit the generated HTML. Default false
            "link": true, //Button to insert a link. Default true
            "image": true, //Button to insert an image. Default true,
            "color": false, //Button to change color of font  
            "size": 'sm' // use button small ion and primary
        });

        $('#stats_table').dataTable( {
          "bStateSave" : true,
          "sDom": 'T<"clear">rtp',
          "aoColumns": [
          {"sType": "string"},
          {"sType": "numeric"}],
          "oTableTools" : {
              "sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
              "aButtons": [
                {
                    "sExtends":    "collection",
                    "sButtonText": "Save",
                    "aButtons":    [ "copy","csv", "xls", "pdf" ]
                }
            ]
            }
          });
        $('#commitments_table').dataTable( {
          "bStateSave" : true,
          "sDom": 'T<"clear">rtp',
           "aoColumns": [
          {"sType": "string"},
          {"sType": "numeric"},
          {"sType": "numeric"}],
          "oTableTools" : {
              "sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
               "aButtons": [
                {
                    "sExtends":    "collection",
                    "sButtonText": "Save",
                    "aButtons":    [ "copy","csv", "xls", "pdf" ]
                }
            ]
          }
          });
         $('#funding_table').dataTable( {
          "bStateSave" : true,
          "sDom": 'T<"clear">rtp',
           "aoColumns": [
          {"sType": "string"},
          {"sType": "numeric"},
          {"sType": "numeric"}],
          "oTableTools" : {
              "sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
               "aButtons": [
                {
                    "sExtends":    "collection",
                    "sButtonText": "Save",
                    "aButtons":    [ "copy","csv", "xls", "pdf" ]
                }
            ]
          }
          });
         $('#remainders_table').dataTable( {
          "bStateSave" : true,
          "sDom": 'T<"clear">rtp',
           "aoColumns": [
          {"sType": "string"},
          {"sType": "numeric"}],
          "oTableTools" : {
              "sSwfPath" : "{!! asset('/media/swf/copy_csv_xls_pdf.swf') !!}",
               "aButtons": [
                {
                    "sExtends":    "collection",
                    "sButtonText": "Save",
                    "aButtons":    [ "copy","csv", "xls", "pdf" ]
                }
            ]
          }
          });
    })
    </script>
@stop