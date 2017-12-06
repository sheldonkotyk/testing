<?php


Route::get('/', function () {
    return view('frontend/views/login');
});

//Silent Post Route for ARB transactions
Route::post('silent_post', 'App\Controllers\Admin\DonationController@authorizeSilentPost');

// // This is for listening to the queries that get run on MySQL
// Event::listen('illuminate.query', function($query)
// {
// 	var_dump($query);
// });


//This filter determines whether the donor is logged in or not 
//and sets 2 variables ($logged_in and $donor_name) across views accordingly
Route::filter('frontend', function () {

    $logged_in='false';
    $order='false';
    $message=null;
    $view_all='false';

    //This is a terrible kludge, but it works! this gets the third segment of the URI, which is the Session_id
    //The frontend views must have the $session_id at the third segment for this to work
    $the_uri= Request::segments();
    $session_id= last($the_uri);
    $redis=RedisL4::connection();

    $page=$the_uri[1];
    $client_id=$the_uri[2];
    $program_id=$the_uri[3];
    
    if ($redis->exists($session_id)==1) {
        $logged_in = $redis->hget($session_id, 'logged_in');
        if ($logged_in=='true') {
            $entity= new Entity;
            $donor_id = $redis->hget($session_id, 'donor_id');

            $donor_name=$entity->getDonorName($donor_id);

            //var_dump ($donor_name);

            View::share('session_donor_name', $donor_name['name']);
        }

        if ($redis->exists($session_id.':saved_entity_id')==1) {
            $order='true';
        }


        $message=$redis->hgetall($session_id.':messages');
        $redis->del($session_id.':messages');
        $view_all=$redis->get($session_id.':view_all');
    }

    $prog = new Program;

    $program_ids=$prog->getPrograms($client_id, $program_id);
    if (count($program_ids)==1) {
        //don't show program name if we are on the donor signup page
        if ($program_ids[0]=='none'||$page=='signup_donor') {
            $program_names=null;
        } else {
            $program_names[0]= Program::where('client_id', $client_id)->where('id', $program_id)->pluck('name');
        }
    } else {
        foreach ($program_ids as $k => $id) {
            $program_names[$k]= Program::where('client_id', $client_id)->where('id', $id)->pluck('name');
        }
    }
    
    View::share('session_program_names', $program_names);
    View::share('session_messages', $message);
    View::share('session_order', $order);
    View::share('session_logged_in', $logged_in);
    View::share('session_view_all', $view_all);
    
    $client = Client::find($client_id);
    if ($client == null) {
        return "Error: Account not found.";
    } else {
        $template = Template::whereClientId($client_id)->first();
        View::share('template', $template);
    }
});

Route::when('frontend/*', 'frontend');

Route::get('backup_db', function () {
    Artisan::call('db:backup', ['--upload-s3' => 'hys-mysql-bu']);
    echo 'done. now see if it worked.';
});


// front end routes
Route::get('login', ['as' => 'admin.login',       'uses' => 'App\Controllers\Admin\AuthController@getLogin']);
Route::post('login', ['as' => 'admin.login.post',  'uses' => 'App\Controllers\Admin\AuthController@postLogin']);
Route::get('logout', ['as' => 'admin.logout',      'uses' => 'App\Controllers\Admin\AuthController@getLogout']);

//Client password reset routes!
Route::get('password/reset', ['as' => 'password.remind' , 'uses' => 'App\Controllers\Admin\RemindersController@getRemind']);
Route::post('password/reset', ['as' => 'password.request' , 'uses' => 'App\Controllers\Admin\RemindersController@postRemind']);
Route::get('password/reset/{token}', [ 'as' => 'password.reset', 'uses' => 'App\Controllers\Admin\RemindersController@getReset']);
Route::post('password/reset/{token}', ['as' => 'password.update', 'uses' => 'App\Controllers\Admin\RemindersController@postReset']);

Route::get('signup', 'App\Controllers\Frontend\SignupController@signUp');
Route::post('signup', 'App\Controllers\Frontend\SignupController@postSignUp');

// Donor front end routes
Route::get('frontend/login/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.login', 'uses' => 'App\Controllers\Donor\AuthController@getLogin']);
Route::get('frontend/reset_password/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.reset_password', 'uses' => 'App\Controllers\Donor\AuthController@resetPassword']);
Route::post('frontend/reset_password/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.reset_password', 'uses' => 'App\Controllers\Donor\AuthController@postResetPassword']);
Route::get('frontend/forgot_username/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.forgot_username', 'uses' => 'App\Controllers\Donor\AuthController@forgotUsername']);
Route::post('frontend/forgot_username/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.forgot_username', 'uses' => 'App\Controllers\Donor\AuthController@postForgotUsername']);


//Route::get('frontend/login/{client_id}/{program_id}', array('as' => 'donor.login.addSession', 'uses' => 'App\Controllers\Donor\AuthController@getLoginAddSession'));
Route::post('frontend/login/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.login.post', 'uses' => 'App\Controllers\Donor\AuthController@postLogin']);
Route::get('frontend/logout/{client_id}/{program_id}/{session_id?}', ['as' => 'donor.logout', 'uses' => 'App\Controllers\Donor\AuthController@getLogout']);

Route::get('test', 'App\Controllers\Frontend\DonorSignupController@test');

Route::get('frontend/signup_donor/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@signUpDonor');
//Route::get('frontend/signup_donor/{client_id}/{program_id}', 'App\Controllers\Frontend\DonorSignupController@signUpDonorAddSession');
Route::post('frontend/signup_donor/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@postAddDonor');

//unified checkout page
Route::get('frontend/order/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkout');

Route::get('frontend/orderD/{client_id}/{hysform_id}/{designation_id}/{session_id?}', 'App\Controllers\Frontend\DesignationCheckoutController@checkoutDesignationsOnly');


Route::post('frontend/checkout_remove_entity/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkoutRemoveEntity');
Route::get('frontend/checkout_update_amount/{client_id}/{program_id}/{entity_id}/{amount}/{currency}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkoutUpdateAmount');
Route::get('frontend/checkout_update_frequency/{client_id}/{program_id}/{entity_id}/{frequency}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkoutUpdateFrequency');
Route::get('frontend/checkout_update_designation_frequency/{client_id}/{program_id}/{designation_id}/{frequency}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkoutUpdateDesignationFrequency');
Route::post('frontend/checkout_signup/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@postCheckoutSignup');
Route::post('frontend/checkout_login/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@postCheckoutLogin');
Route::post('frontend/logged_in_checkout/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@loggedInCheckout');

Route::post('frontend/checkout_add_designation/{client_id}/{program_id}/{currency}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkoutAddDesignation');
Route::post('frontend/checkout_remove_designation/{client_id}/{program_id}/{id}/{currency}/{session_id?}', 'App\Controllers\Frontend\DonorSignupController@checkoutRemoveDesignation');


//Designation only checkout page routes
Route::post('frontend/checkout_add_designation_only/{client_id}/{hysform_id}/{designation_id}/{currency}/{session_id?}', 'App\Controllers\Frontend\DesignationCheckoutController@checkoutAddDesignation');
Route::post('frontend/checkout_remove_designation_only/{client_id}/{hysform_id}/{designation_id}/{id}/{currency}/{session_id?}', 'App\Controllers\Frontend\DesignationCheckoutController@checkoutRemoveDesignation');
Route::get('frontend/checkout_update_designation_frequency_only/{client_id}/{hysform_id}/{designation_id}/{id}/{frequency}/{session_id?}', 'App\Controllers\Frontend\DesignationCheckoutController@checkoutUpdateDesignationFrequency');
Route::post('frontend/checkout_signup_only/{client_id}/{hysform_id}/{designation_id}/{session_id?}', 'App\Controllers\Frontend\DesignationCheckoutController@postCheckoutSignup');
Route::post('frontend/checkout_login_only/{client_id}/{hysform_id}/{designation_id}/{session_id?}', 'App\Controllers\Frontend\DesignationCheckoutController@postCheckoutLogin');

// Logged in Donor routes
Route::get('frontend/donor_view/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Donor\DonorController@donorView');
Route::get('frontend/donor_view_entity/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Donor\DonorController@DonorViewEntity');
Route::get('frontend/donor_view_entity_messages/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Donor\DonorController@DonorViewEntityMessageHistory');
Route::get('frontend/donor_view_entity_compose_message/{client_id}/{program_id}/{entity_id}/{parent_id}/{session_id?}', 'App\Controllers\Donor\DonorController@DonorViewEntityCompose');

Route::post('frontend/donor_view_entity_compose_message/{client_id}/{program_id}/{entity_id}/{parent_id}/{session_id?}', 'App\Controllers\Donor\DonorController@postDonorViewEntity');

Route::get('frontend/donor_update_card/{client_id}/{program_id}/{session_id}', 'App\Controllers\Donor\DonorController@updateCard');
Route::post('frontend/donor_update_card/{client_id}/{program_id}/{session_id}', 'App\Controllers\Donor\DonorController@postUpdateCard');

Route::get('frontend/donor_update_info/{client_id}/{program_id}/{session_id}', 'App\Controllers\Donor\DonorController@updateInfo');
Route::post('frontend/donor_update_info/{client_id}/{program_id}/{session_id}', 'App\Controllers\Donor\DonorController@postUpdateInfo');

Route::get('frontend/modify_amount/{client_id}/{program_id}/{commitment_id}/{session_id}/', 'App\Controllers\Donor\DonorController@modifyCommitmentAmount');
Route::post('frontend/modify_amount/{client_id}/{program_id}/{commitment_id}/{session_id}/', 'App\Controllers\Donor\DonorController@postModifyCommitmentAmount');


Route::get('frontend/donor_upload/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Donor\DonorController@donorUploadFile');
Route::post('donor/upload_file/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Donor\DonorController@postUploadFile');
Route::post('donor/recordUpload/{client_id}/{session_id}', 'App\Controllers\Donor\DonorController@postRecordUpload');
Route::get('donor/files_table/{client_id}/{program_id}/{id}/{entity_id}/{session_id?}', 'App\Controllers\Donor\DonorController@filesTable');
Route::get('donor/delete_file/{client_id}/{program_id}/{id}/{entity_id}/{session_id?}', 'App\Controllers\Donor\DonorController@deleteFile');


// email routes
Route::get('activate_user/{id}/{activationCode}', 'App\Controllers\Admin\UserController@activateUser');
Route::get('opt_out/{who}/{id}', 'App\Controllers\Admin\EmailController@optOut');


//Display entities and Files to Public
//Route::get('frontend/view_all/{session_id}/{client_id}', 'App\Controllers\Frontend\FrontendController@RedirectToFirstProgram');
Route::get('frontend/view_all/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\FrontendController@DisplayTitlesAndFiles');
Route::get('frontend/view_pages/{client_id}/{program_id}/{session_id?}', 'App\Controllers\Frontend\FrontendController@DisplayTitlesAndFilesPagination');
Route::get('frontend/view_entity/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Frontend\FrontendEntityController@ViewEntity');
Route::get('frontend/random/{client_id}/{program_id}', 'App\Controllers\Frontend\FrontendEntityController@ViewRandomEntity');

Route::post('frontend/save_entity/{client_id}/{program_id}/{entity_id}/{session_id?}', 'App\Controllers\Frontend\FrontendEntityController@SaveEntity');
Route::get('frontend/save_entity/{client_id}/{program_id}/{entity_id}/{session_id}/{amount}/{frequency}', 'App\Controllers\Frontend\FrontendEntityController@GetSaveEntity');

// Cron routes
Route::any('cron/run_daily', 'App\Controllers\Frontend\CronController@runDailyCharges');
Route::get('cron/test_config/{client_id}', 'App\Controllers\Frontend\CronController@testConfig');
// Route::get('cron/test', 'App\Controllers\Frontend\CronController@testDiffInDays');

// must be logged in to access these routes - admin routes
Route::group(['before' => 'auth.admin'], function () {
    $permissions = Session::get('permissions');
    View::share('permissions', $permissions);
        
    // get menus
    View::composer('admin.sidenav', 'MenuComposer');
    View::composer('admin.topnav', 'MenuComposer');
    
    Route::get('admin', 'App\Controllers\Admin\DashboardController@index');
    Route::get('admin/upgrades', 'App\Controllers\Admin\DashboardController@upgrades');
    Route::get('admin/reload_stats', 'App\Controllers\Admin\DashboardController@reloadStats');
    
    // Client account
    Route::get('admin/edit_client_account', 'App\Controllers\Admin\ClientController@editClientAccount');
    Route::post('admin/edit_client_account', 'App\Controllers\Admin\ClientController@postEditClientAccount');
    Route::get('admin/update_client_cc', 'App\Controllers\Admin\ClientController@updateClientCC');
    Route::post('admin/update_client_cc', 'App\Controllers\Admin\ClientController@postUpdateClientCC');
    Route::get('admin/update_client_cc', 'App\Controllers\Admin\ClientController@updateClientCC');
    Route::post('admin/update_client_cc', 'App\Controllers\Admin\ClientController@postUpdateClientCC');
    
    Route::get('admin/email_settings', 'App\Controllers\Admin\ClientController@emailSettings');
    Route::post('admin/email_settings', 'App\Controllers\Admin\ClientController@postEmailSettings');
    
    Route::get('admin/mailgun_logs', 'App\Controllers\Admin\ClientController@mailgunLogs');
    Route::get('admin/mailgun_logs_data', 'App\Controllers\Admin\ClientController@mailgunLogsData');
    
    Route::post('admin/switch_client', 'App\Controllers\Admin\ClientController@switchClient');
    
    // Template routes
    Route::get('admin/template', 'App\Controllers\Admin\TemplateController@template');
    Route::post('admin/template', 'App\Controllers\Admin\TemplateController@postTemplate');
    Route::post('admin/upload_pic_to_template', 'App\Controllers\Admin\TemplateController@postUploadPicToTemplate');
    Route::get('admin/remove_template_pic/{id}', 'App\Controllers\Admin\TemplateController@deleteTemplatePic');
    
    // Program routes
    Route::get('admin/get_entity_data/{id}', 'App\Controllers\Admin\DataGridController@ExportEntityData');
    Route::get('admin/print_entity_data/{id}', 'App\Controllers\Admin\DataGridController@DisplayTheGrid');
    Route::get('admin/print_entity_data_table/{id}', 'App\Controllers\Admin\DataGridController@DisplayTable');

    Route::get('admin/manage_program', 'App\Controllers\Admin\ProgramController@manageProgram');
    Route::get('admin/edit_program/{program_id}', 'App\Controllers\Admin\ProgramController@editProgram');
    Route::post('admin/edit_program/{program_id}', 'App\Controllers\Admin\ProgramController@postEditProgram');
    Route::get('admin/remove_program/{program_id}', 'App\Controllers\Admin\ProgramController@removeProgram');
    Route::get('admin/remove_program_warning/{program_id}', 'App\Controllers\Admin\ProgramController@removeProgramWarning');
    Route::get('admin/program_settings/{id}', 'App\Controllers\Admin\ProgramController@programSettings');
    Route::get('admin/add_child_program/{id}', function ($id) {
        return view('admin/views/addNewChild')->with('parent_id', $id);
    });
    Route::get('admin/add_sub_program', 'App\Controllers\Admin\ProgramController@addSubProgram');

    Route::post('admin/add_child_program/{id}', 'App\Controllers\Admin\ProgramController@postChildProgram');
    Route::post('admin/add_sub_program', 'App\Controllers\Admin\ProgramController@postSubProgram');
    Route::post('admin/updatetree', 'App\Controllers\Admin\ProgramController@updateTree');
    Route::post('admin/add_sponsorship_form_to_program/{id}', 'App\Controllers\Admin\ProgramController@postSponsorshipToProgram');
    Route::post('admin/add_donor_form_to_program/{id}', 'App\Controllers\Admin\ProgramController@postDonorToProgram');
    Route::post('admin/add_settings_to_program/{id}', 'App\Controllers\Admin\ProgramController@postSettingsToProgram');
    Route::post('admin/add_emailsets_to_program/{id}', 'App\Controllers\Admin\ProgramController@postEmailsetsToProgram');
    Route::post('admin/add_submit_form_to_program/{id}', 'App\Controllers\Admin\ProgramController@postSubmitFormToProgram');
    Route::get('admin/remove_submit_form/{type}/{program_id}/{form_id}', 'App\Controllers\Admin\ProgramController@removeSubmitForm');

    // Forms
    Route::get('admin/create_form/{default_type?}', 'App\Controllers\Admin\HysformController@createForm');
    Route::post('admin/create_form', 'App\Controllers\Admin\HysformController@postCreateForm');
    Route::get('admin/manage_form/{hysform_id}', 'App\Controllers\Admin\HysformController@manageForm');
    Route::get('admin/remove_form/{hysform_id}', 'App\Controllers\Admin\HysformController@removeForm');
    Route::get('admin/edit_form/{hysform_id}', 'App\Controllers\Admin\HysformController@editForm');
    Route::post('admin/edit_form/{hysform_id}', 'App\Controllers\Admin\HysformController@postEditForm');
    Route::get('admin/forms', 'App\Controllers\Admin\HysformController@forms');
    
    // Form fields
    Route::get('admin/add_form_field/{id}/{type}', 'App\Controllers\Admin\FieldController@addFormField');
    Route::post('admin/add_form_field/{id}/{type}', 'App\Controllers\Admin\FieldController@postAddFormField');
    Route::get('admin/update_field_order/{type}', 'App\Controllers\Admin\FieldController@updateFieldsOrder');
    Route::get('admin/edit_form_field/{id}/{type}', 'App\Controllers\Admin\FieldController@editFormField');
    Route::post('admin/edit_form_field/{id}/{type}', 'App\Controllers\Admin\FieldController@postEditFormField');
    Route::get('admin/delete_form_field/{id}/{type}', 'App\Controllers\Admin\FieldController@deleteFormField');
    Route::post('admin/delete_form_field/{id}/{type}', 'App\Controllers\Admin\FieldController@postDeleteFormField');

    Route::get('admin/delete_form/{id}', 'App\Controllers\Admin\FieldController@deleteForm');
    Route::post('admin/delete_form/{id}', 'App\Controllers\Admin\HysformController@removeForm');
    
    // Entity routes
    Route::get('admin/add_entity/{program_id}', 'App\Controllers\Admin\EntityController@addEntity');
    Route::post('admin/add_entity/{program_id}', 'App\Controllers\Admin\EntityController@postAddEntity');
    Route::get('admin/show_all_entities/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@showAllEntities');
    Route::get('admin/show_all_sponsorships/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@showAllSponsorships');
    Route::get('admin/show_all_entities_table/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@showAllEntitiesTable');
    Route::get('admin/show_all_entities_ajax/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@showAllEntitiesAjax');
    Route::get('admin/show_all_sponsorships_table/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@sponsoredTable');
    Route::get('admin/show_all_sponsorships_ajax/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@showAllSponsorshipsAjax');
    Route::post('admin/field_options/{program_id}/{type?}', 'App\Controllers\Admin\EntityController@postFieldOptions');
    Route::get('admin/field_options/{program_id}', 'App\Controllers\Admin\EntityController@postFieldOptions');
    Route::get('admin/field_options/{program_id}/{type}', 'App\Controllers\Admin\EntityController@fieldOptions');
    Route::get('admin/move_entity/{entity_id}', 'App\Controllers\Admin\EntityController@moveEntity');
    Route::post('admin/move_entity/{entity_id}', 'App\Controllers\Admin\EntityController@postMoveEntity');

    Route::get('admin/edit_entity/{id}', 'App\Controllers\Admin\EntityController@editEntity');
    Route::post('admin/edit_entity/{id}', 'App\Controllers\Admin\EntityController@postEditEntity');
    Route::get('admin/remove_entity/{entity_id}', 'App\Controllers\Admin\EntityController@removeEntity');
    Route::post('admin/remove_entities/{program_id}', 'App\Controllers\Admin\EntityController@removeEntities');

    Route::get('admin/activate_entity/{entity_id}', 'App\Controllers\Admin\EntityController@activateEntity');
    Route::post('admin/activate_entities/{program_id}', 'App\Controllers\Admin\EntityController@activateEntities');

    Route::get('admin/select_saved_report/{report_id}/{program_id}/{trashed?}', 'App\Controllers\Admin\EntityController@selectSavedReport');
    Route::get('admin/remove_saved_report/{report_id}/{program_id}', 'App\Controllers\Admin\EntityController@removeSavedReport');
    Route::get('admin/delete_entity/{entity_id}', 'App\Controllers\Admin\EntityController@permanentlyDeleteEntity');
    Route::post('admin/delete_entities/{program_id}', 'App\Controllers\Admin\EntityController@deleteEntities');
    
    Route::get('admin/delete_all_entities/{program_id}', function ($program_id) {
        $entities = Entity::withTrashed()->whereProgramId($program_id)->get();
        $redis = RedisL4::connection();
        foreach ($entities as $entity) {
            $hash = "id:{$entity->id}";
            $redis->del($hash);
            $entity->forceDelete();
            echo "Deleted: ".$hash."<br>";
        }
        echo "All done!";
    });
    
    Route::get('admin/delete_all_donors/{hysform_id}', function ($hysform_id) {
        $donors = Donor::withTrashed()->where('hysform_id', $hysform_id)->get();
        $redis = RedisL4::connection();
        foreach ($donors as $donor) {
            $hash = "donor:id:{$donor->id}";
            $redis->del($hash);
            $donor->forceDelete();
            echo "Deleted: ".$hash."<br>";
        }
        echo "All done!";
    });
    
    // Submit forms - Form Archives
    Route::get('admin/submit_form/{type}/{id}/{program_id}/{form_id}', 'App\Controllers\Admin\FormArchiveController@submitForm');
    Route::post('admin/submit_form/{type}/{id}/{program_id}/{form_id}', 'App\Controllers\Admin\FormArchiveController@postSubmitForm');
    Route::get('admin/view_archived_form/{archived_form_id}', 'App\Controllers\Admin\FormArchiveController@viewArchivedForm');
    Route::get('admin/edit_archived_form/{archived_form_id}', 'App\Controllers\Admin\FormArchiveController@editArchivedForm');
    Route::post('admin/edit_archived_form/{archived_form_id}', 'App\Controllers\Admin\FormArchiveController@postEditArchivedForm');
    Route::get('admin/list_archived_forms/{type}/{id}/{hysform_id}', 'App\Controllers\Admin\FormArchiveController@listArchivedForms');
    Route::get('admin/archived_report', 'App\Controllers\Admin\FormArchiveController@archivedReport');
    Route::post('admin/archived_report', 'App\Controllers\Admin\FormArchiveController@postArchivedReport');
    
    // Notifications
    Route::get('admin/submit_form_notification/{program_id}/{form_id}', 'App\Controllers\Admin\NotificationController@submitFormNotification');
    Route::post('admin/submit_form_notification/{program_id}/{form_id}', 'App\Controllers\Admin\NotificationController@postSubmitFormNotification');

    // Upload routes
    Route::get('admin/upload_file/{type}/{id}', 'App\Controllers\Admin\UploadController@uploadFile');
    Route::post('admin/upload_file/{type}/{id}', 'App\Controllers\Admin\UploadController@postUploadFile');
    Route::get('admin/files_table/{type}/{id}', 'App\Controllers\Admin\UploadController@filesTable');
    Route::get('admin/rotate_pic/{id}', 'App\Controllers\Admin\UploadController@rotatePic');
    Route::get('admin/make_profile/{id}', 'App\Controllers\Admin\UploadController@makeProfile');
    Route::get('admin/make_placeholder/{id}', 'App\Controllers\Admin\UploadController@makePlaceholder');
    Route::get('admin/edit_file/{id}', 'App\Controllers\Admin\UploadController@editFile');
    Route::post('admin/edit_file/{id}', 'App\Controllers\Admin\UploadController@postEditFile');
    Route::get('admin/delete_file/{id}', 'App\Controllers\Admin\UploadController@deleteFile');
    
    // CSV Import
    Route::get('admin/csv_import/{program_id}', 'App\Controllers\Admin\UploadController@csvImport');
    Route::post('admin/csv_import/{program_id}', 'App\Controllers\Admin\UploadController@postcsvImport');
    Route::post('admin/csv_process/{program_id}', 'App\Controllers\Admin\UploadController@postProcessCSV');
    Route::post('admin/csv_process_relationships/{program_id}', 'App\Controllers\Admin\UploadController@postProcessRelationshipsCSV');
    Route::post('admin/csv_process_payments/{program_id}', 'App\Controllers\Admin\UploadController@postProcessPaymentsCSV');
    Route::get('admin/create_thumbnails', 'App\Controllers\Admin\UploadController@thumbExisting');
    
    //Upload posts file info to DB after direct S3 upload
    Route::post('admin/recordUpload', 'App\Controllers\Admin\UploadController@postRecordUpload');



    //Get the box response after authorization
    Route::get('admin/box_response', 'App\Controllers\Admin\UploadController@boxResponse');

    // Notes
    Route::get('admin/notes/{id}/{type}/{program_id}/{cat?}', 'App\Controllers\Admin\NoteController@viewNotes');
    Route::post('admin/add_note/{type}/{program_id}', 'App\Controllers\Admin\NoteController@postNewNote');
    Route::get('admin/list_cat/{type}/{program_id}', 'App\Controllers\Admin\NoteController@listCat');
    Route::get('admin/edit_note/{note_id}/{program_id}', 'App\Controllers\Admin\NoteController@editNote');
    Route::post('admin/edit_note/{note_id}/{program_id}', 'App\Controllers\Admin\NoteController@postEditNote');
    
    // Donors
    Route::get('admin/add_donor/{hysform_id}', 'App\Controllers\Admin\DonorController@addDonor');
    Route::post('admin/add_donor/{hysform_id}', 'App\Controllers\Admin\DonorController@postAddDonor');
    Route::get('admin/show_all_donors/{hysform_id}/{trashed?}', 'App\Controllers\Admin\DonorController@showAllDonors');
    Route::get('admin/edit_donor/{id}', 'App\Controllers\Admin\DonorController@editDonor');
    Route::post('admin/edit_donor/{id}', 'App\Controllers\Admin\DonorController@postEditDonor');
    Route::get('admin/archive_donor/{donor_id}', 'App\Controllers\Admin\DonorController@removeDonor');
    Route::post('admin/archive_donors/{donor_id}', 'App\Controllers\Admin\DonorController@removeDonors');
    Route::get('admin/delete_donor/{donor_id}', 'App\Controllers\Admin\DonorController@deleteDonor');
    Route::post('admin/delete_donors/{hysform_id}', 'App\Controllers\Admin\DonorController@deleteDonors');
    Route::get('admin/activate_donor/{donor_id}/{with_commitments?}', 'App\Controllers\Admin\DonorController@activateDonor');
    Route::post('admin/activate_donors/{hysform_id}/{with_commitments?}', 'App\Controllers\Admin\DonorController@activateDonors');
    Route::post('admin/donor_field_options/{hysform_id}', 'App\Controllers\Admin\DonorController@postFieldOptions');
    Route::get('admin/donor_field_options/{hysform_id}/{type}', 'App\Controllers\Admin\DonorController@fieldOptions');
    Route::get('admin/show_all_donors_table/{hysform_id}/{trashed?}', 'App\Controllers\Admin\DonorController@showAllDonorsTable');
    Route::get('admin/show_all_donors_ajax/{hysform_id}/{trashed?}', 'App\Controllers\Admin\DonorController@showAllDonorsAjax');
    Route::get('admin/select_donor_saved_report/{report_id}/{hysform_id}/{trashed?}', 'App\Controllers\Admin\DonorController@selectDonorSavedReport');
    Route::get('admin/remove_donor_saved_report/{report_id}/{hysform_id}', 'App\Controllers\Admin\DonorController@removeDonorSavedReport');
    Route::post('admin/send_notify_donors/{hysform_id}/{emailset_id}', 'App\Controllers\Admin\DonorController@sendNotifyDonors');
    Route::post('admin/send_year_end_donors/{hysform_id}/{emailset_id}/{year}', 'App\Controllers\Admin\DonorController@sendYearEndDonors');

    // Sponsorships
    Route::get('admin/list_available_entities', 'App\Controllers\Admin\DonorController@listAvailableEntities');
    Route::get('admin/sponsorships/{id}', 'App\Controllers\Admin\DonorController@sponsorships');
    Route::post('admin/sponsorships/{id}', 'App\Controllers\Admin\DonorController@postSponsorshipsNext');
    Route::post('admin/add_sponsorships/{id}', 'App\Controllers\Admin\DonorController@postAddSponsorships');
    Route::get('admin/remove_sponsorship/{donor_entity_id}', 'App\Controllers\Admin\DonorController@removeSponsorship');
    Route::get('admin/restore_sponsorship/{donor_entity_id}', 'App\Controllers\Admin\DonorController@restoreSponsorship');
    Route::get('admin/all_sponsorships', 'App\Controllers\Admin\DonorEntityController@allSponsorships');

    //Check Donor Entity Errors
    Route::get('admin/check_donor_entity_errors/{fix?}', 'App\Controllers\Admin\DonorEntityController@checkDonorEntityErrors');
    
    // Groups
    Route::get('admin/create_group', 'App\Controllers\Admin\GroupController@createGroup');
    Route::post('admin/create_group', 'App\Controllers\Admin\GroupController@postCreateGroup');
    Route::get('admin/view_groups', 'App\Controllers\Admin\GroupController@viewGroups');
    Route::get('admin/edit_group/{group_id}', 'App\Controllers\Admin\GroupController@editGroup');
    Route::post('admin/edit_group/{group_id}', 'App\Controllers\Admin\GroupController@postEditGroup');
    Route::get('admin/remove_group/{group_id}', 'App\Controllers\Admin\GroupController@removeGroup');
    
    // Admins
    Route::get('admin/add_admin', 'App\Controllers\Admin\UserController@addAdmin');
    Route::post('admin/add_admin', 'App\Controllers\Admin\UserController@postAddAdmin');
    Route::get('admin/view_admins', 'App\Controllers\Admin\UserController@viewAdmins');
    Route::get('admin/edit_admin/{user_id}', 'App\Controllers\Admin\UserController@editAdmin');
    Route::post('admin/edit_admin/{user_id}', 'App\Controllers\Admin\UserController@postEditAdmin');
    Route::get('admin/remove_admin/{user_id}', 'App\Controllers\Admin\UserController@removeAdmin');
    Route::get('admin/manual_account_activation/{user_id}', 'App\Controllers\Admin\UserController@manuallyActivateUser');
    
    // Settings
    Route::get('admin/settings', 'App\Controllers\Admin\SettingController@settings');
    Route::get('admin/add_settings', 'App\Controllers\Admin\SettingController@addSettings');
    Route::post('admin/add_settings', 'App\Controllers\Admin\SettingController@postAddSettings');
    Route::get('admin/edit_settings/{settings_id}', 'App\Controllers\Admin\SettingController@editSettings');
    Route::post('admin/edit_settings/{settings_id}', 'App\Controllers\Admin\SettingController@postEditSettings');
    Route::get('admin/remove_settings/{settings_id}', 'App\Controllers\Admin\SettingController@removeSettings');

    //Multi-Program select
    Route::get('admin/multi_program_select', 'App\Controllers\Admin\ProgramController@multiProgramSelect');
    Route::post('admin/multi_program_select', 'App\Controllers\Admin\ProgramController@postMultiProgramSelect');
    
    
    // Email
    Route::get('admin/email', 'App\Controllers\Admin\EmailController@emailManage');
    Route::get('admin/add_emailset', 'App\Controllers\Admin\EmailController@addEmailset');
    Route::post('admin/add_emailset', 'App\Controllers\Admin\EmailController@postAddEmailset');
    Route::get('admin/edit_emailset/{emailset_id}', 'App\Controllers\Admin\EmailController@editEmailset');
    Route::post('admin/edit_emailset/{emailset_id}', 'App\Controllers\Admin\EmailController@postEditEmailset');
    Route::get('admin/edit_emailtemplate/{emailset_id}/{trigger}', 'App\Controllers\Admin\EmailController@editEmailtemplate');
    Route::post('admin/edit_emailtemplate/{emailset_id}/{trigger}', 'App\Controllers\Admin\EmailController@postEditEmailtemplate');
    Route::get('admin/remove_emailtemplate/{template_id}', 'App\Controllers\Admin\EmailController@removeEmailtemplate');
    Route::get('admin/send_email/{program_id}/{trigger}/{to}/{donor_id?}/{entity_id?}', 'App\Controllers\Admin\EmailController@sendEmail');
    Route::get('admin/remove_emailset/{emailset_id}', 'App\Controllers\Admin\EmailController@removeEmailset');
    Route::get('admin/auto_email_s3_upload', 'App\Controllers\Admin\EmailController@autoEmailS3Upload');
    Route::get('admin/change_default_emailset/{hyform_id}/{emailset_id}', 'App\Controllers\Admin\HysformController@ChangeDefaultEmailset');
    
    
    // Donoremails - Email manager
    Route::get('admin/email_manager', 'App\Controllers\Admin\DonoremailController@viewEmailManager');
    Route::get('admin/view_email/{email_id}', 'App\Controllers\Admin\DonoremailController@viewEmail');
    Route::post('admin/assign_admin/{email_id}', 'App\Controllers\Admin\DonoremailController@assignAdmin');
    Route::get('admin/update_email_status/{email_id}', 'App\Controllers\Admin\DonoremailController@updateEmailStatus');
    Route::post('admin/send_email_response/{email_id}', 'App\Controllers\Admin\DonoremailController@sendEmailResponse');
    Route::get('admin/send_message/{entity_id}/{donor_id}/{from}/{file_id?}', 'App\Controllers\Admin\DonoremailController@sendMessage');
    Route::post('admin/send_message/{entity_id}/{donor_id}/{from}/{file_id?}', 'App\Controllers\Admin\DonoremailController@postSendMessage');

    
    // Designations
    Route::get('admin/all_designations', 'App\Controllers\Admin\DesignationController@viewAllDesignations');
    Route::get('admin/add_designation', 'App\Controllers\Admin\DesignationController@addDesignation');
    Route::post('admin/add_designation', 'App\Controllers\Admin\DesignationController@postAddDesignation');
    Route::get('admin/edit_designation/{designation_id}', 'App\Controllers\Admin\DesignationController@editDesignation');
    Route::post('admin/edit_designation/{designation_id}', 'App\Controllers\Admin\DesignationController@postEditDesignation');
    Route::get('admin/remove_designation/{designation_id}', 'App\Controllers\Admin\DesignationController@removeDesignation');
    
    // Donations
    Route::get('admin/donations', 'App\Controllers\Admin\DonationController@donationsOptions');
    // Route::post('admin/donations', 'App\Controllers\Admin\DonationController@viewAllDonations');
    Route::get('admin/view_donations/{all?}', 'App\Controllers\Admin\DonationController@viewAllDonations');
    Route::post('admin/view_donations/{all?}', 'App\Controllers\Admin\DonationController@viewAllDonations');
    Route::get('admin/form_fields', 'App\Controllers\Admin\DonationController@formFields');
    //Route::get('admin/edit_donation/{donation_id}', 'App\Controllers\Admin\DonationController@editDonation');
    Route::get('admin/remove_donation/{donation_id}', 'App\Controllers\Admin\DonationController@removeDonation');
    Route::get('admin/donations_by_donor/{donor_id}', 'App\Controllers\Admin\DonationController@donationsByDonor');
    Route::post('admin/add_donation/{donor_id}', 'App\Controllers\Admin\DonationController@addDonation');
    Route::get('admin/add_cc/{donor_id}', 'App\Controllers\Admin\DonationController@addCC');
    Route::post('admin/add_cc/{donor_id}', 'App\Controllers\Admin\DonationController@postAddCC');
    Route::post('admin/update_cc/{donor_id}', 'App\Controllers\Admin\DonationController@updateCC');
    Route::get('admin/delete_cc/{donor_id}', 'App\Controllers\Admin\DonationController@deleteCC');
    Route::get('admin/edit_donation/{donation_id}', 'App\Controllers\Admin\DonationController@editDonation');
    Route::post('admin/edit_donation/{donation_id}', 'App\Controllers\Admin\DonationController@postEditDonation');
    //Route::get('admin/remove_donation/{donation_id}', 'App\Controllers\Admin\DonationController@removeDonation');
    Route::get('admin/commitment_donation/{commitment_id}', 'App\Controllers\Admin\DonationController@addCommitmentDonation');
    Route::post('admin/commitment_donation/{commitment_id}', 'App\Controllers\Admin\DonationController@postAddCommitmentDonation');
    
    // Commitments
    Route::get('admin/edit_commitment/{commitment_id}', 'App\Controllers\Admin\CommitmentController@editCommitment');
    Route::post('admin/edit_commitment/{commitment_id}', 'App\Controllers\Admin\CommitmentController@postEditCommitment');
    Route::get('admin/fix_commitment/{donor_entity_id}', 'App\Controllers\Admin\CommitmentController@fixCommitment');
    Route::get('admin/remove_commitment/{commitment_id}', 'App\Controllers\Admin\CommitmentController@RemoveCommitment');
    Route::get('admin/send_new_donor_email/{commitment_id}', 'App\Controllers\Admin\DonorController@sendSignupEmail');

    //Error display
    Route::get('admin/error', function () {
        return view('admin.views.error');
    });
    //Info display
    Route::get('admin/info', function () {
        return view('admin.views.info');
    });

    //Authorize.net Silent Post Testing URL
    Route::get('silent_post', 'App\Controllers\Admin\DonationController@authorizeSilentPostTest');

    Route::get('admin/move_donors', 'App\Controllers\Admin\DonorController@moveDonorsToSQL');
    Route::get('admin/move_entities', 'App\Controllers\Admin\EntityController@moveEntitiesToSQL');

    Route::get('admin/update_all_statuses/{client_id}/{key}', 'App\Controllers\Admin\EntityController@updateAllStatuses');
        

    //Fix Funding Settings
    // Route::get('admin/fix_funding_settings','App\Controllers\Admin\SettingController@fixFundingSettings');

    //Queues
    Route::get('queue_something', function () {
        
        Queue::push(function ($job) {
            Log::info('Queues are pretty sweet');
            
            $job->delete();
        });
    });
    
    
    Route::get('clear_queue', function () {
        $queue = config('queue.connections.beanstalkd.queue');

        $pheanstalk = Queue::getPheanstalk();
        $pheanstalk->useTube($queue);
        $pheanstalk->watch($queue);
 
        while ($job = $pheanstalk->reserve(0)) {
            $pheanstalk->delete($job);
        }
    });
        
        
    // handling app errors
    if (1==2) {
        App::error(function ($exception, $code) {
            $url = Request::url();
            Log::error($exception, ['code' => $code, 'url' => $url, 'inputs' => Input::all(), 'ip' => Request::getClientIp() ]);
            
/*
			if (404 != $code) {
				$data = array('exception' => $exception, 'url' => $url, 'inputs' => Input::all(), 'client' => Session::get('client_id'));
			    Mail::queue('emails.error', $data, function($message)
			    {
			        $message->to('support@helpyousponsor.com')->subject('HYS Error');
			    });
			}
*/
           
            if (Request::is('admin/*')) {
                switch ($code) {
                    case 403:
                        return Response::view('admin.views.errors.403', [], 403);
            
                    case 404:
                        return Response::view('admin.views.errors.404', [], 404);
            
                    case 500:
                        return Response::view('admin.views.errors.500', [], 500);
            
                    default:
                        return Response::view('admin.views.errors.default', [], $code);
                }
            } else if (Request::is('frontend/*')) {
                return Response::view('frontend.views.error', [], $code);
            }
        });
    }
    
    Route::get('show_environment', function () {
        var_dump(App::environment());
        echo '<br>';
        var_dump(URL::to('/'));
    });
});
