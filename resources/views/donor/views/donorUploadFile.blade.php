@extends('frontend.default')

@section('headerscripts')
	
	{!! HTML::style('css/jquery-ui-1.10.4.css') !!}
	{!! HTML::style('plupload/js/jquery.ui.plupload/css/jquery.ui.plupload.css') !!}


	{!!HTML::script('js/jquery-2.0.3.min.js')!!}
	{!!HTML::script('js/jquery-ui-1.10.4.min.js')!!}


	{!!HTML::script('js/offline.min.js')!!}
	<!-- {!!HTML::script('offlinejs-simulate-ui-master/offline-simulate-ui.min.js')!!} -->

	<!-- {!!HTML::script('plupload/js/plupload.dev.js')!!}
	{!!HTML::script('plupload/js/jquery.ui.plupload/jquery.ui.plupload.js')!!} -->
	

	{!!HTML::script('plupload/js/plupload.full.min.js')!!}
	{!!HTML::script('plupload/js/jquery.ui.plupload/jquery.ui.plupload.js')!!}
	<!-- {!!HTML::script('plupload/js/moxie.js')!!}	 -->

	{!!HTML::script('js/canvas-to-blob.js')!!}
	

	{!! HTML::style('css/dropzone.css') !!}
	{!! HTML::script('js/dropzone.min.js') !!}


	<?php
		$profile['id']=$id;
		// important variables that will be used throughout this example
		$bucket = 'hys';
		 
		// these can be found on your Account page, under Security Credentials > Access Keys
		$accessKeyId = 'AKIAJYYK3NCWARJIECIQ';
		$secret = 'LfaW7HcIzavgTVFUUy4ve89dSgD+upZJ+b+E0kEv';
		 
		$policy = base64_encode(json_encode(array(
		  // ISO 8601 - date('c'); generates uncompatible date, so better do it manually
		  'expiration' => date('Y-m-d\TH:i:s.000\Z', strtotime('+1 day')), 
		  'conditions' => array(
		        array('bucket' => $bucket),
		        array('acl' => 'public-read'),
		        array('starts-with', '$key', ''),
		        // Plupload internally adds name field, so we need to mention it here
		        
		        //Rather than redirecting, this sends a 201 code of successful HTTP request
		       // array('success_action_status'=>'201'),

		        array('starts-with', '$name', ''),  
		        // One more field to take into account: Filename - gets silently sent by FileReference.upload() in Flash
		        // http://docs.amazonwebservices.com/AmazonS3/latest/dev/HTTPPOSTFlash.html
		        array('starts-with', '$Filename', ''), 
		    )
		)));
		$signature = base64_encode(hash_hmac('sha1', $policy, $secret, $raw_output = true));
	?>

<script>
$(document).ready(function() {
	Dropzone.autoDiscover = false;
	$("div#file_upload").dropzone({ 
		url: "{!! URL::to('donor/upload_file', array($client_id,$program_id,$entity_id,$session_id)) !!}", 
		maxFilesize: 3,
		maxFiles: {!!$number_of_files_allowed!!},
		dictDefaultMessage: '',
        complete: function()
        {
	        $.ajax({
				url: "{!! URL::to('donor/files_table', array($client_id,$program_id , $id,$entity_id,$session_id)) !!}",
				data: {},
				cache: 'false',
				dataType: 'html',
				type: 'get',
				success: function(html, textStatus) {
					$('div#files').html(html);
				}
			});
        }
	});
    $.ajax({
		url: "{!! URL::to('donor/files_table', array($client_id,$program_id,$id,$entity_id,$session_id)) !!}",
		data: {},
		cache: 'false',
		dataType: 'html',
		type: 'get',
		success: function(html, textStatus) {
			$('div#files').html(html);
		}
	});

});
</script>
@stop

@section('content')

@if (Session::get('message'))
    <div class="alert alert-{!! Session::get('alert') !!}">{!! Session::get('message') !!}</div>
@endif

	
	@if($files_full!=true)
	<!-- <div id="file_upload" class="dropzone"></div>	 -->
	<div id="panel1" class="panel panel-default magic-element width-full">
	            <div class="panel-heading">
		            <div class="panel-actions">
	                    	<div class="pull-right label label-danger offline" style="display:none;">Internet Not Connected</div>
	                    	<div class="pull-right label label-info loading" style="display:none;">Uploading</div>
	                    	
	                </div>
	                <div class="panel-icon notloading"><i class="icon ion-upload"></i></div>
	                <h3 class="panel-title notloading">Upload images and files  </h3>
 					
 					
 					<div class="panel-title loading pull-left" style="display:none;" >
		                    	<div class="spinner spinner-rectangle-bounce" >
		                            <div class="rect1"></div>
		                            <div class="rect2"></div>
		                            <div class="rect3"></div>
		                            <div class="rect4"></div>
		                            <div class="rect5"></div>
                       	 		</div>
	                    		
	                    	</div>

 					<div class="panel-title loading" style="display:none;" > Upload in progress </div>
 					
	                
	                
	               <!--  <div class="progress progress-striped active">
		                    <div class="progress-bar progress-bar-info" aria-valuetransitiongoal="100" aria-valuenow="100" style="width: 50%;"></div>
		                </div> -->
	            </div><!-- /panel-heading -->
	            <div class="panel-body">
	            
	<!-- <div class="alert alert-info alert-icon">
		<div class="icon">
			<i class="icon ion-ios7-information-empty"></i>
		</div>
        <strong>Upload Instructions</strong> <br/>Add files to upload. Only .jpg, .gif and .png are allowed for the profile image.
    </div> -->

	@if($upload->useBox($client_id)&&$box_access_token!=''&&$upload->isBoxLoggedIn($client_id))
		<div id="uploader" class="uploader online">
		    <p>Your browser doesn't have Flash, Silverlight or HTML5 support. Use Dropzone! You will be limited to 2 meg uploads.</p>
		    <div class="reverse-well reverse-well-small"><p class="text-center">Drop files below or click the box to select files for upload</p></div>
			<div id="file_upload" class="dropzone"></div>
		</div>
	@else
		<div id="uploader" class="uploader online">
	    	<p>Your browser doesn't have Flash, Silverlight or HTML5 support. Use Dropzone! You will be limited to 2 meg uploads.</p>
	    	<div class="reverse-well reverse-well-small"><p class="text-center">Drop files below or click the box to select files for upload</p></div>
			<div id="file_upload" class="dropzone"></div>
		</div>
	    
	@endif

	<div class="alert alert-danger offline" style="display:none;">Internet connection problems detected.</div>
	

	<!-- <div id="uploader" class="uploader"> -->
	   <!--  <p>Your browser doesn't have Flash, Silverlight or HTML5 support. Use Dropzone! You will be limited to 2 meg uploads.</p>
	    <div class="reverse-well reverse-well-small"><p class="text-center">Drop files below or click the box to select files for upload</p></div>
		<div id="file_upload" class="dropzone"></div> -->
	<!-- </div> -->
		
	</div>
	</div>

	@else
	<div class="reverse-well reverse-well-small"><h4><p class="text-center">You may not upload more than 5 files</p></h4><p>Delete some files and upload again to </p></div>
	@endif
	<hr>
	<h2>Donor Files</h2>
	
	<div id="files">
		{{-- Ajax loaded --}}
	</div>
	
@stop
@section('footerscripts')

<script>



$(function() {

		var i=0;
		var boxSuccess = 0;
		var s3Success = 0;
		var numFiles = 0;

		var 
            $loading = $('.loading'),
            $notloading = $('.notloading');

		var 
            $online = $('.online'),
            $offline = $('.offline');

        Offline.on('confirmed-down', function () {
            $online.fadeOut(function () {
                $offline.fadeIn();
            });
        });

        Offline.on('confirmed-up', function () {
            $offline.fadeOut(function () {
                $online.fadeIn();
            });
        });


		

	$("#uploader").plupload({
		runtimes : 'html5,flash,silverlight',
		url : 'https://s3-us-west-1.amazonaws.com/{!!$bucket!!}/',
		// urlstream_upload: true,
		buttons:{browse:true,start:false,stop:false},
		
		multipart: true,
		multipart_params: {
			'key': '${filename}', // use filename as a key
			'filename': '${filename}', // adding this to keep consistency across the runtimes
			'acl': 'public-read',
			'AWSAccessKeyId' : '<?php echo $accessKeyId; ?>',		
			'policy': '<?php echo $policy; ?>',
			'signature': '<?php echo $signature; ?>'
		},


		// !!!Important!!! 
		// this is not recommended with S3, since it will force Flash runtime into the mode, with no progress indication
		resize : {width:500,quality : 75},  // Resize images on clientside, if possible 
		
		// optional, but better be specified directly
		file_data_name: 'file',

		filters : {
			// Maximum file size
			max_file_size : '30mb',
			// Specify what files to browse for
			mime_types: [
				{title : "Image files", extensions : "jpg,jpeg"},
				{title : "PDFs", extensions : "pdf"},
				{title : "Documents", extensions : "doc,xls,csv"},
				{title : "Zip files", extensions : "zip"},
				{title : "All Files", extensions : "*"},

			]
		},

		dragdrop: true,

		// Flash settings
		flash_swf_url : '/plupload/js/Moxie.swf',

		// Silverlight settings
		silverlight_xap_url : '/plupload/js/Moxie.xap',

		preinit : {
 
            UploadFile: function(up, file) {


            	window.onbeforeunload = confirmExit;

            	$notloading.fadeOut(function () {
                $loading.fadeIn();
            	});

                var tmp = '${filename}';
                var ext = getFileExtension(file.name);
                i++;
                up.settings.multipart_params.key = '{!!$slug!!}'+ i + '.' + ext;
            	file.hys_type= "{!!$type!!}";
            	file.hys_id= "{!!$id!!}";
            	file.hys_entity_id= "{!!$entity_id!!}";
            	file.hys_slug = '{!!$slug!!}' + i ;
            	file.hys_ext= ext;

            	//Reject non-jpeg files larger than 3 Megs for upload to S3
            	if(file.type!='image/jpeg'&&file.size>3000000)
            	{
            		file.status = plupload.FAILED;
					var boxInfo = '';
					if("{!!$upload->useBox($client_id)!!}" == "1")
						boxInfo = '<br/>This file will be sent to Box.com, but not S3.';
					$('#uploader').plupload('notify', 'info', 'HYS Upload Failed: File must be smaller than 3 megabytes<br/> (This file is: ' +bytesToSize(file.size)+')'+ boxInfo );
				}

            	if("{!!$upload->useBox($client_id)!!}" == "1" && file.type =='image/jpeg') //This is for uploading images to BOX
				{
	            	//Send the Full size image to box.
	            	var img = new mOxie.Image();

			        img.onload = function() {

			        	
			        	if (img.width > 6500 || img.height > 6500) {
		                    // Throw an error to the user if the image is to large (pixel-wise, not MB wise.)
							numFiles--;
		                    file.status = plupload.FAILED;
							up.trigger('Error', {
			                    code : -300, // IO_ERROR
			                    message : 'Upload Failed: File dimensions must be less than 6500px by 6500px.<br/> (This file is: ' +img.width+'px by '+img.height+'px.)',
			                    details : file.name + ' failed.',
			                    file : file
		                	});
		                	return;
		                   }
					
			        	//Grabs the full size file for submission to BOX!
			        	var base64Data = img.getAsDataURL(file.type);

			        	//Convert the base64 file to a blob for uploading to Box
			        	var theDataBlob = window.dataURLtoBlob(base64Data);

						var form = new FormData();

					    // JS file-like object
						var blob = new Blob([theDataBlob], { type: file.type });
						 
						// Add the file to the form and passing the name along
						//form.append('file', blob, file.hys_slug + '.' + file.hys_ext); //Change to File Name!
						form.append('file', blob, file.name); //Change to File Name!
						 
						// This will place all the files in the root directory of the Box App.
						form.append('parent_id', '{!!$box_folder_id!!}');

						//This is the upload API url
						var uploadUrl = 'https://upload.box.com/api/2.0/files/content';
						 
						// The Box OAuth 2 Header. Access token.
						var headers = {
						    Authorization: 'Bearer {!!$box_access_token!!}'
						};
						 
						$('#uploader').plupload('notify', 'info', file.name + '  upload to Box.com has started. Please wait...');
						
						$.ajax({
						    url: uploadUrl,
						    headers: headers,
						    type: 'POST',
						    // This prevents JQuery from trying to append the form as a querystring
						    processData: false,
						    contentType: false,
						    data: form
						}).complete(function ( data ) {

							 // console.log('data =' + JSON.stringify(data));
							 // console.log('message =' + data.message);
							 // console.log('status =' + data.status);
						 	//  console.log('code =' + data.code);
						 	//  console.log('help_url =' + data.help_url);


							if(data.statusText=="Conflict")
							{
								numFiles--;
								file.status = plupload.FAILED;
								up.trigger('Error', {
			                    code : -300, // IO_ERROR
			                    message : 'Upload Failed: This file already exists in your Box.com account under the "{!!urlencode($program->name)!!}[id-{!!$program->id!!}]" folder. You must either delete the original file on Box.com, or rename this file to upload it.',
			                    details : file.name + ' failed.',
			                    file : file
		                	});
								//This posts the file info to the DB
					        	$.ajax({
								    url: "{!!URL::to('donor/recordUpload',array($client_id,$session_id))!!}",
								    type: 'POST',
								    data: { 
									    'name'	 		: file.name,
									    'status' 		: 'box_duplicate',
									    'file_id'		: file.id,
									    'type'			: file.type,
									    'hys_slug'		: file.hys_slug,
									    'hys_type'		: file.hys_type,
									    'hys_id'		: file.hys_id,
									    'hys_entity_id' : file.hys_entity_id,
									    'hys_ext'		: file.hys_ext,
										'box_url'		: file.box_url,
									    'email'			: 'false'
									},
								    dataType: "json",

								}).complete(function ( data3 ) {
								    // Log the JSON response to prove this worked
								     console.log(data3.responseText);
									$.ajax({
										url: "{!! URL::to('donor/files_table', array($client_id,$program_id,$id,$entity_id,$session_id)) !!}",
										data: {},
										cache: 'false',
										dataType: 'html',
										type: 'get',
										success: function(html, textStatus) {
											refreshFiles(html);
										}
									});
								});
								return;

							}
							else
							{

							var response = jQuery.parseJSON(data.responseText);

							file.box_id = response.entries[0].id;

							// Add the destination folder for the upload to the form
							 
							var uploadUrl = 'https://api.box.com/2.0/files/' + file.box_id;
							 
							// The Box OAuth 2 Header. Add your access token.
							var headers = {
							    Authorization: 'Bearer {!!$box_access_token!!}'
							};

								$.ajax({
							    url: uploadUrl,
							    headers: headers,
							    type: 'PUT',
							    processData: false,
						    	contentType: false,
							    data: '{"shared_link": { "access" : "open" }}'
							}).complete(function ( data2 ) 
							{
								

								var response = jQuery.parseJSON(data2.responseText);


								file.box_url= '';

								if(typeof response.shared_link.download_url != 'undefined'){

									boxSuccess++;
									file.box_url= response.shared_link.download_url;

									var uploading='Uploading, please wait...<br/>';
									if(boxSuccess==numFiles&&s3Success==numFiles)
									{
										uploading='Upload Complete. Scroll down to view your files.<br/>';

										window.onbeforeunload = null;
										$loading.fadeOut(function () {
						                $notloading.fadeIn();
						            	});

										//up.splice();
										//Reset things here!
									}

									var boxMessage= '';
									if("{!!$upload->useBox($client_id)!!}" == "1")
										boxMessage = boxSuccess + ' of '+ numFiles +' files were Successfully uploaded to Box.com<br/>';

									s3Message = s3Success + ' of '+ numFiles +' files were Successfully uploaded to S3';
									
									$('#uploader').plupload('notify', 'info', uploading+ boxMessage);

								}
								else
								{
									$('#uploader').plupload('notify', 'info', file.name + ' failed to upload to Box.com');
								}

								//This posts the file info to the DB
					        	$.ajax({
								    url: "{!!URL::to('donor/recordUpload',array($client_id,$session_id))!!}",
								    type: 'POST',
								    data: { 
									    'name'	 		: file.name,
									    'status' 		: file.status,
									    'file_id'		: file.id,
									    'type'			: file.type,
									    'hys_slug'		: file.hys_slug,
									    'hys_type'		: file.hys_type,
									    'hys_id'		: file.hys_id,
									    'hys_entity_id' : file.hys_entity_id,
									    'hys_ext'		: file.hys_ext,
										'box_url'		: file.box_url,
										'email'			: 'false'
									},
								    dataType: "json",

								}).complete(function ( data3 ) {
								    // Log the JSON response to prove this worked
								     console.log(data3.responseText);
									$.ajax({
											url: "{!! URL::to('donor/files_table', array($client_id,$program_id,$id,$entity_id,$session_id)) !!}",
										data: {},
										cache: 'false',
										dataType: 'html',
										type: 'get',
										success: function(html, textStatus) {
											refreshFiles(html);
										}
									});
								});

							});
						}	

						});
			        };

			        img.onerror = function() {
		                console.log("Error!");
		         	};

			        var source = file.getSource();
			        
			        img.load(source);

			    }
			    else if("{!!$upload->useBox($client_id)!!}" == "1") //This is for uploading everything but images to BOX!
			    {
			    	//Send the Full size image to box.
	            	var fr = new mOxie.FileReader();

			        fr.onload = function() {

			        	//Grabs the full size file for submission to BOX!
			        	var base64Data = this.result;

			        	//Convert the base64 file to a blob for uploading to Box
			        	var theDataBlob = window.dataURLtoBlob(base64Data);

						var form = new FormData();

					    // JS file-like object
						var blob = new Blob([theDataBlob], { type: file.type });
						 
						// Add the file to the form and passing the name along
						form.append('file', blob, file.name); //change back to 
						 
						// This will place all the files in the root directory of the Box App.
						form.append('parent_id', '{!!$box_folder_id!!}');
						 
						//This is the upload API url
						var uploadUrl = 'https://upload.box.com/api/2.0/files/content';
						 
						// The Box OAuth 2 Header. Access token.
						var headers = {
						    Authorization: 'Bearer {!!$box_access_token!!}'
						};
						 
						$.ajax({
						    url: uploadUrl,
						    headers: headers,
						    type: 'POST',
						    // This prevents JQuery from trying to append the form as a querystring
						    processData: false,
						    contentType: false,
						    data: form
						}).complete(function ( data ) {

							if(data.statusText=="Conflict")
							{
								numFiles--;
								file.status = plupload.FAILED;
								up.trigger('Error', {
			                    code : -300, // IO_ERROR
			                    message : 'Upload Failed: This file already exists in your Box.com account under the "{!!urlencode($program->name)!!}[id-{!!$program->id!!}]" folder. You must either delete the original file on Box.com, or rename this file to upload it.',
			                    details : file.name + ' failed.',
			                    file : file
		                	});
								//This posts the file info to the DB
					        	$.ajax({
								    url: "{!!URL::to('donor/recordUpload',array($client_id,$session_id))!!}",
								    type: 'POST',
								    data: { 
									    'name'	 		: file.name,
									    'status' 		: 'box_duplicate',
									    'file_id'		: file.id,
									    'type'			: file.type,
									    'hys_slug'		: file.hys_slug,
									    'hys_type'		: file.hys_type,
									    'hys_id'		: file.hys_id,
									    'hys_entity_id' : file.hys_entity_id,
									    'hys_ext'		: file.hys_ext,
										'box_url'		: file.box_url,
										'email'			: 'false'
									},
								    dataType: "json",

								}).complete(function ( data3 ) {
								    // Log the JSON response to prove this worked
								     console.log(data3.responseText);
									$.ajax({
										url: "{!! URL::to('donor/files_table', array($client_id,$program_id,$id,$entity_id,$session_id)) !!}",
										data: {},
										cache: 'false',
										dataType: 'html',
										type: 'get',
										success: function(html, textStatus) {
											$('div#files').html(html);
										}
									});
								});
								return;

							}

							var response = jQuery.parseJSON(data.responseText);

							file.box_id = response.entries[0].id;

							// Add the destination folder for the upload to the form
							 
							var uploadUrl = 'https://api.box.com/2.0/files/' + file.box_id;
							 
							// The Box OAuth 2 Header. Add your access token.
							var headers = {
							    Authorization: 'Bearer {!!$box_access_token!!}'
							};

								$.ajax({
							    url: uploadUrl,
							    headers: headers,
							    type: 'PUT',
							    processData: false,
						    	contentType: false,
							    data: '{"shared_link": { "access" : "open" }}'
							}).complete(function ( data2 ) 
							{
								// console.log(data2.responseText);

								var response = jQuery.parseJSON(data2.responseText);


								file.box_url= '';

								if(typeof response.shared_link.download_url != 'undefined'){

									boxSuccess++;
									file.box_url= response.shared_link.download_url;

									var uploading='Uploading, please wait...<br/>';
									if(boxSuccess==numFiles&&s3Success==numFiles)
									{
										uploading='Upload Complete. Scroll down to view your files.<br/>';
										//up.splice();
										//Reset things here!
									}

									var boxMessage= '';
									if("{!!$upload->useBox($client_id)!!}" == "1")
										boxMessage = boxSuccess + ' of '+ numFiles +' files were Successfully uploaded to Box.com<br/>';

									s3Message = s3Success + ' of '+ numFiles +' files were Successfully uploaded to S3';
									
									$('#uploader').plupload('notify', 'info', uploading+ boxMessage);

								}
								else
								{

									$('#uploader').plupload('notify', 'info', file.name + ' failed to upload to Box.com');
								}

								//This posts the file info to the DB
					        	$.ajax({
								    url: "{!!URL::to('donor/recordUpload',array($client_id,$session_id))!!}",
								    type: 'POST',
								    data: { 
									    'name'	 		: file.name,
									    'status' 		: '5',
									    'file_id'		: file.id,
									    'type'			: file.type,
									    'hys_slug'		: file.hys_slug,
									    'hys_type'		: file.hys_type,
									    'hys_id'		: file.hys_id,
									    'hys_entity_id' : file.hys_entity_id,
									    'hys_ext'		: file.hys_ext,
										'box_url'		: file.box_url,
										'email'			: 'false',
									},
								    dataType: "json",

								}).complete(function ( data3 ) {
								    // Log the JSON response to prove this worked
								    

								    console.log(data3.responseText);
									$.ajax({
											url: "{!! URL::to('donor/files_table', array($client_id,$program_id,$id,$entity_id,$session_id)) !!}",
										data: {},
										cache: 'false',
										dataType: 'html',
										type: 'get',
										success: function(html, textStatus) {
											refreshFiles(html);
										}
									});
								});

							});

						});
			        };

			        fr.onerror = function() {
		                console.log("Error!");
		         	};

			        var source = file.getSource();
			        
			        fr.readAsDataURL(source);

			    }
            	
            }
        },

		init : {

			
		FilesAdded: function(up, files) {
			
			//This automatically starts upload when the client adds a file or files	
			numfiles=0;
			$.each(files, function(i, file) {
				numFiles++;
			});


			up.start();

    		},


       	 FileUploaded: function(up, file, info) {
                // Called when file has finished uploading
                //log('[FileUploaded] File:', file, "Info:", info);

            },

        UploadComplete: function(up, files) {
        	
        	//Record the upload to DB!
        	//s3Success=0;
        	$.each(files, function(i, file) {

        		//This posts the file info to the DB
        		if(file.status==5 && file.uploaded!='done'){
        			
        			s3Success++;
        			var uploading='Uploading, please wait...<br/>';
					if("{!!$upload->useBox($client_id)!!}" == "1"&&boxSuccess==numFiles&&s3Success==numFiles)
					{
						uploading='Upload Complete. Scroll down to view your files.<br/>';
						window.onbeforeunload = null;

						$loading.fadeOut(function () {
		                $notloading.fadeIn();
		            	});
						//up.splice();
					}
					if("{!!$upload->useBox($client_id)!!}" != "1"&&s3Success==numFiles)
					{
						uploading='Upload Complete. Scroll down to view your files.<br/>';
						window.onbeforeunload = null;
						$loading.fadeOut(function () {
		                $notloading.fadeIn();
		            	});
						//up.splice();
					}

					var boxMessage= '';
					if("{!!$upload->useBox($client_id)!!}" == "1"&&boxSuccess==numFiles)
						boxMessage = boxSuccess + ' of '+ numFiles +' files were Successfully uploaded to Box.com<br/>';

					s3Message = s3Success + ' of '+ numFiles +' files were Successfully uploaded to S3';
					
					$('#uploader').plupload('notify', 'info', uploading+ boxMessage);

					file.uploaded = 'done'; //Keeps this file from being counted again.

		        	$.ajax({
					    url: "{!!URL::to('donor/recordUpload',array($client_id,$session_id))!!}",
					    type: 'POST',
					    data: { 
						    'name'	 		: file.name,
						    'status' 		: file.status,
						    'file_id'		: file.id,
						    'type'			: file.type,
						    'hys_slug'		: file.hys_slug,
						    'hys_type'		: file.hys_type,
						    'hys_id'		: file.hys_id,
						    'hys_entity_id' : file.hys_entity_id,
						    'hys_ext'		: file.hys_ext,
							'box_url'		: '',
						    'email'			: 'true'

						},
					    dataType: "json",

					}).complete(function ( data3 ) {
					    // Log the JSON response to prove this worked
					      console.log(data3.responseText);
						
					});
				}

        	}); //End each file loop

        	//Display the new file(s)
        	$.ajax({
				url: "{!! URL::to('donor/files_table', array($client_id,$program_id,$id,$entity_id,$session_id)) !!}",
				data: {},
				cache: 'false',
				dataType: 'html',
				type: 'get',
				success: function(html, textStatus) {
					refreshFiles(html);
				}
			});


			//This automatically removes the files from the uploader
			//up.splice();

        } //End Upload complete function
        

    }

	});
});

function refreshFiles(html)
{
    $('div#files').html( html );  // random content, just for demo
}


function confirmExit()
{
    return "Warning: Your file upload is in progress!!";
}

function getFileExtension(filename) {

	return filename.split('.').pop();
}

function bytesToSize(bytes) {
   if(bytes == 0) return '0 Byte';
   var k = 1000;
   var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
   var i = Math.floor(Math.log(bytes) / Math.log(k));
   return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
}



	
</script>

@stop