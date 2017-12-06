

    <?php
        $the_uri= Request::segments();
        $page= last($the_uri);

        if(isset($id))
        {
            $profile['id']=$id;
            $profile['program_id']=$program_id;
        }

        //var_dump($the_uri);

        $edit_entity='default';
        $edit_entity_url=URL::to('admin/edit_entity', array($profile['id']));
        if(isset($the_uri[1])&&$the_uri[1]=='edit_entity')
        {
            $edit_entity='default active';
            $edit_entity_url='#';
        }
       

        $upload_file='default';
        $upload_file_url= URL::to('admin/upload_file', array('entity', $profile['id']));
        if(isset($the_uri[1])&&$the_uri[1]=='upload_file')
        {
            $upload_file='default active';
            $upload_file_url='#';
        }

        $move_pre='';
        $move_post='';
        $move_url=URL::to('admin/move_entity/'.$profile['id']);
        if(isset($the_uri[1])&&$the_uri[1]=='move_entity')
        {
            $move_pre='<strong>';
            $move_post='</strong>';
            $move_url='#';
        }

        $notes_pre='';
        $notes_post='';
        $notes_url=URL::to('admin/notes', array($profile['id'], 'entity', $profile['program_id']));
        if(isset($the_uri[1])&&$the_uri[1]=='notes')
        {
            $notes_pre='<strong>';
            $notes_post='</strong>';
            $notes_url='#';
        }


        $remove_icon= 'glyphicon glyphicon-trash';
        $remove_text= 'Archive';
        $remove_url = URL::to('admin/remove_entity/'.$profile['id']);

        $all_recipients_url    =URL::to('admin/show_all_entities', array($profile['program_id']));
        $all_recipients_text    ='View All Recipients';
        $all_recipients_icon     ='glyphicon glyphicon-align-justify';

        if($entity['deleted_at']!=null)
        {

            $restore_icon= 'glyphicon glyphicon-repeat';
            $restore_text= 'Restore';
            $restore_url = URL::to('admin/activate_entity/'.$profile['id']);

            $all_recipients_url    = URL::to('admin/show_all_entities', array($profile['program_id'],'1'));
            $all_recipients_text    = 'View All Archived Recipients';
            $all_recipients_icon     = 'glyphicon glyphicon-trash';

        }

    ?>
           


<ul class="nav nav-pills">

	  <li><div class="btn-group">
	            <button type="button" class="btn btn-{!!$edit_entity!!}" onClick="window.location='{!!$edit_entity_url!!}'">
	               <span class="glyphicon glyphicon-pencil"></span> Edit Profile
	            </button>
             <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu" role="menu">
            	<li><a href="{!!$notes_url!!}"><span class="icon ion-android-note"></span> {!!$notes_pre!!}Notes{!!$notes_post!!}</a></li>
            	<li><a href="{!!$move_url!!}"><span class="icon ion-arrow-right-a"></span>{!!$move_pre!!} Move {!!$move_post!!}</a></li>
            	
                @if($entity['deleted_at']==null&&(!isset($permissions->disable_entity_archive)||$permissions->disable_entity_archive!='1'))
                    <li><a href="{!!$remove_url!!}"><span class="{!!$remove_icon!!}"></span> {!!$remove_text!!} </a>
                @endif
                
                 @if($entity['deleted_at']!=null)
                    @if(!isset($permissions->disable_entity_restore)||$permissions->disable_entity_restore!='1')
                        <li><a href="{!!$restore_url!!}"><span class="{!!$restore_icon!!}"></span> {!!$restore_text!!} </a>
                    @endif
                    @if(!isset($permissions->disable_entity_delete)||$permissions->disable_entity_delete!='1')
                        <li><a href="{!!URL::to('admin/delete_entity',array($profile['id']))!!}"><span class="glyphicon glyphicon-remove"></span> Delete </a></li>
                    @endif
                 @endif

                @if(count($submit)>0)
            	<li class="divider"></li>
            	@endif
            	@foreach ($submit as $s)
			  		<li><a href="{!! URL::to('admin/list_archived_forms', array('entity', $profile['id'], $s->id)) !!}"><span class="icon ion-ios7-browsers"></span> {!! $s->name !!}</a></li>
			  	@endforeach
            </ul>
            </div></li>

	  <li><div class="btn-group"><a href="{!!$upload_file_url!!}">
            <button type="button" class="btn btn-{!!$upload_file!!}">
               <span class="icon ion-upload"></span> Upload Files and Photos
            </button></a></div></li>
	  
      <li><div class="btn-group"><a href="{!! $all_recipients_url !!}">
            <button type="button" class="btn btn-default">
               <span class="{!!$all_recipients_icon!!}"></span> {!!$all_recipients_text!!}
     </button></a></div></li>
    </ul>   



          
   <div class="app-body">
       <div id="collapseTwo" class="panel panel-default panel-collapse collapse">
                <div class="panel-heading">
                    <div class="panel-icon"><i class="glyphicon glyphicon-link"></i></div>
                    <div class="panel-actions">
                            <div class="label label-success">Info</div>
                    </div>
                    <h3 class="panel-title"> Share <strong> {!!$name!!}</strong></h3>
                </div><!-- /panel-heading -->
                      <div class="panel-body">
                      <h4 >Iframe Embed Code </h4>
                      <pre class="prettyprint">&lt;iframe class="hysiframe" src="{!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}" style="border:0px #FFFFFF none;" name="HYSiFrame" scrolling="no" frameborder="1" height="1500px" marginheight="0px" marginwidth="0px" width="100%"&gt;&lt;/iframe&gt;</pre>
                      <br>
                      <h4><strong>{!!$name!!}</strong> Frontend Link: <a href="{!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}" target="_blank">{!!URL::to('frontend/view_entity',array(Session::get('client_id'),$program->id,$profile['id']))!!}</a></h4>
                      <h4><strong>{!!$program->name!!}</strong> Random Single Recipient Link: <a href="{!! URL::to('frontend/random', array(Session::get('client_id'), $program->id)) !!}" target="_blank">{!! URL::to('frontend/random', array(Session::get('client_id'),$program->id)) !!}</a></h4>                      

                      </div>
                    </div>


    </div>
    
       