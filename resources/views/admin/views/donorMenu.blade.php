

    <?php
        $the_uri= Request::segments();
        $page= last($the_uri);

        if(isset($donor->id))
        {
            $profile['id']=$donor->id;
            $profile['hysform_id']=$donor->hysform_id;
        }

        //var_dump($the_uri);

        $edit_donor='default';
        $edit_donor_url=URL::to('admin/edit_donor', array($profile['id']));
        if(isset($the_uri[1])&&$the_uri[1]=='edit_donor')
        {
            $edit_donor='default active';
            $edit_donor_url='#';
        }

        $upload_pre='';
        $upload_post='';
        $upload_file_url= URL::to('admin/upload_file', array('donor', $profile['id']));
        if(isset($the_uri[1])&&$the_uri[1]=='upload_file')
        {
            $upload_pre='<strong>';
            $upload_post='</strong>';
            $upload_file_url='#';
        }

        $notes_pre='';
        $notes_post='';
        $notes_url=URL::to('admin/notes', array($profile['id'], 'donor', 'donor'));
        if(isset($the_uri[1])&&$the_uri[1]=='notes')
        {
            $notes_pre='<strong>';
            $notes_post='</strong>';
            $notes_url='#';
        }

        $_sponsorships='default';
        $sponsorships_url=URL::to('admin/sponsorships', array($profile['id']));
        if(isset($the_uri[1])&&$the_uri[1]=='sponsorships')
        {
            $_sponsorships='default active';
            $sponsorships_url='#';
        }

         $_donations='default';
        $donations_url=URL::to('admin/donations_by_donor', array($profile['id']));
        if(isset($the_uri[1])&&$the_uri[1]=='donations_by_donor')
        {
            $_donations='default active';
            $donations_url='#';
        }



        $remove_icon= 'glyphicon glyphicon-trash';
        $remove_text= 'Archive';
        $remove_url = URL::to('admin/archive_donor/'.$profile['id']);



        $all_donors_url     = URL::to('admin/show_all_donors', array($profile['hysform_id']));
        $all_donors_text    = 'View All Donors';
        $all_donors_icon    = 'glyphicon glyphicon-align-justify';

        if($donor->deleted_at!=null)
        {
            $restore_icon= 'glyphicon glyphicon-repeat';
            $restore_text= 'Restore';
            $restore_url = URL::to('admin/activate_donor/'.$profile['id'].'/1');

            $all_donors_url     = URL::to('admin/show_all_donors', array($profile['hysform_id'],'1'));
            $all_donors_text    = 'View All Archived Donors';
            $all_donors_icon    = 'glyphicon glyphicon-trash';

        }


    ?>

    


<ul class="nav nav-pills">

	  <li><div class="btn-group">
	            <button type="button" class="btn btn-{!!$edit_donor!!}" onClick="window.location='{!!$edit_donor_url!!}'">
	               <span class="glyphicon glyphicon-pencil"></span> Edit Profile
	            </button>
             <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu" role="menu">
            	<li><a href="{!!$notes_url!!}"><span class="icon ion-android-note"></span> {!!$notes_pre!!}Notes{!!$notes_post!!}</a></li>
                <li><a href="{!!$upload_file_url!!}"><span class="icon ion-upload"></span> {!!$upload_pre!!}Upload Files and Photos{!!$upload_post!!}</a></li>

                @if($donor->deleted_at==null&&(!isset($permissions->disable_donor_archive)||$permissions->disable_donor_archive!='1'))
                    <li><a href="{!!$remove_url!!}"><span class="{!!$remove_icon!!}"></span> {!!$remove_text!!} </a></li>
                @endif
                
                @if($donor->deleted_at!=null)
                    @if(!isset($permissions->disable_donor_restore)||$permissions->disable_donor_restore!='1')
                        <li><a href="{!!$restore_url!!}"><span class="{!!$restore_icon!!}"></span> {!!$restore_text!!} </a></li>
                    @endif
                    
                    @if(!isset($permissions->disable_donor_delete)||$permissions->disable_donor_delete!='1')
                        <li><a href="{!!URL::to('admin/delete_donor',array($profile['id']))!!}"><span class="glyphicon glyphicon-remove"></span> Delete </a></li>
                    @endif
                @endif
                
                
            </ul>
            </div></li>

            @if(isset($emailsets)&&$donor->deleted_at==null)
         <li> <div class="dropdown">
              <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <span class="glyphicon glyphicon-envelope"></span>
                Email
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-left" aria-labelledby="dropdownMenu1">
               @if(count($emailsets['emailsets'])>0)
                    @if(in_array('notify_donor',$emailsets['active_triggers']))
                        <li><a href="#" id="notify"><span class="glyphicon glyphicon-envelope"></span> Send Account Setup Notification @if(count($template_errors[$emailsets['default_emailset']['id']]['notify_donor'])>1){!!reset($template_errors[$emailsets['default_emailset']['id']]['notify_donor'])!!} @endif</a></li>
                        <li class="divider"></li>
                    @else
                        <li><a href="{!!URL::to('admin/edit_emailtemplate',array($emailsets['default_emailset']['id'],'notify_donor'))!!}" ><span class="glyphicon glyphicon-envelope"></span> You must setup the "Notify Donor of Account Setup" Email Template to send this email.</a></li>
                        <li class="divider"></li>
                    @endif
                @endif
                
                 @if(count($years)==0)
                    <li> <a href="#"> No Donations could be found. </a></li>
                @endif
                @foreach($years as $year)

                    @if(count($emailsets['emailsets'])>0)
                           <li><a href="#" id ="statement_{!!$year!!}"><span class="glyphicon glyphicon-file"></span> Send {!!$year!!} Year End Statement @if(count($template_errors[$emailsets['default_emailset']['id']]['year_end_statement'])>1){!!reset($template_errors[$emailsets['default_emailset']['id']]['year_end_statement'])!!} @endif</a></li>
                    @endif
                @endforeach

                @if(count($emailsets['emailsets'])>0)
                <li class="divider"></li>
                    <li> <a href="{!!URL::to('admin/email')!!}"><span class="glyphicon glyphicon-send"></span> Currenlty using Emailset: <strong> {!!$emailsets['default_emailset']['name']!!}</strong> @if(count($template_errors[$emailsets['default_emailset']['id']]['notify_donor'])>1||count($template_errors[$emailsets['default_emailset']['id']]['year_end_statement'])>1) <span class="label label-warning">View Warnings</span> @endif</a></li>
                <li class="divider"></li>
                @endif

                @foreach($emailsets['emailsets'] as $k => $set)
                    @if($k!=$emailsets['default_emailset']['id'])
                        <li><a href="{!!URL::to('admin/change_default_emailset',array($hysform->id,$k))!!}"><span class="glyphicon glyphicon-transfer"></span> Switch to <strong>{!!$set['name']!!}</strong> emailset</a></li>
                    @endif
                @endforeach
                
               
              </ul>
            </div></li>
                @endif
            

        <li><div class="btn-group"><a href="{!!$sponsorships_url!!}">
                <button type="button" class="btn btn-{!!$_sponsorships!!}">
                   <span class="glyphicon glyphicon-link"></span> Sponsorships
                </button></a></div></li>

         <li><div class="btn-group"><a href="{!!$donations_url!!}">
            <button type="button" class="btn btn-{!!$_donations!!}">
               <span class="glyphicon glyphicon-usd"></span> Donations
            </button></a></div></li>
	  
      <li><div class="btn-group"><a href="{!! $all_donors_url !!}">
            <button type="button" class="btn btn-default">
               <span class="{!!$all_donors_icon!!}"></span> {!!$all_donors_text!!}
            </button></a></div></li>

       
          

	  
	   
	</ul>

    {!! HTML::script('assets/messenger/js/messenger.min.js')!!}
    {!!HTML::script('assets/messenger/js/messenger-theme-flat.js')!!}
@if(isset($emailsets))
    <script>

        $(document).ready( function () 
        {
            var msgOpt = function(place, theme){
                    Messenger.options = {
                        extraClasses: 'messenger-fixed ' + place,
                        theme: theme
                    }
                }

            @foreach($years as $year)

            $('#statement_{!!$year!!}').click( function () {
                var array = [];

                array.push({!!$profile['id']!!});

                $.ajax({
                    url: "{!! URL::to('admin/send_year_end_donors',array($profile['hysform_id'],$emailsets['default_emailset']['id'],$year)) !!}",
                    data: { 'donor_ids':array },
                    cache: 'false',
                    dataType: 'html',
                    type: 'post',
                    success: function(html, textStatus) {
                                
                                html = JSON.parse(html);
                                
                                var place = 'messenger-on-top',
                                theme = 'flat';

                                msgOpt(place, theme);

                                var success_message = html['success_message'];
                                var error_message = html['error_message'];

                                if(error_message)
                                {
                                  Messenger().post({message: error_message ,
                                    hideAfter: 500,
                                    type: "error",
                                    showCloseButton: true});
                                }
                                 if(success_message)
                                {
                                    Messenger().post({message: success_message ,
                                        hideAfter: 500,
                                        type: "success",
                                        showCloseButton: true});
                                }

                            }
                });     
                
            });

            @endforeach

             $('#notify').click( function () {
                var array = [];
                
                array.push({!!$profile['id']!!});
                
                $.ajax({
                    url: "{!! URL::to('admin/send_notify_donors',array($profile['hysform_id'],$emailsets['default_emailset']['id'])) !!}",
                    data: { 'donor_ids':array },
                    cache: 'false',
                    dataType: 'html',
                    type: 'post',
                    success: function(html, textStatus) {
                                html = JSON.parse(html);
                                
                                var place = 'messenger-on-top',
                                theme = 'flat';

                                msgOpt(place, theme);

                                var success_message = html['success_message'];
                                var error_message = html['error_message'];

                                if(error_message)
                                {
                                  Messenger().post({message: error_message ,
                                    hideAfter: 500,
                                    type: "error",
                                    showCloseButton: true});
                                }

                                 if(success_message)
                                {
                                    Messenger().post({message: success_message ,
                                        hideAfter: 500,
                                        type: "success",
                                        showCloseButton: true});
                                }

                            }
                });     
            });
        });
    </script>
@endif