<?php

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('user', 'UserCrudController');
    Route::crud('invite', 'InviteCrudController');
    Route::crud('team', 'TeamCrudController');
    Route::crud('teamxlsform', 'TeamXlsformCrudController');
    Route::crud('xlsform', 'XlsformCrudController');
    Route::post('teamxlsform/{team_xlsform}/deploytokobo', 'TeamXlsformCrudController@deployToKobo');
    Route::post('teamxlsform/{team_xlsform}/syncdata', 'TeamXlsformCrudController@syncData');
    Route::post('teamxlsform/{team_xlsform}/archive', 'TeamXlsformCrudController@archiveOnKobo');
    Route::crud('datamap', 'DataMapCrudController');
    Route::crud('variable', 'VariableCrudController');
    Route::crud('choice', 'ChoiceCrudController');
    Route::crud('teamsubmission', 'TeamSubmissionCrudController');
    Route::crud('team_member', 'TeamMemberCrudController');
}); // this should be the absolute last line of this file