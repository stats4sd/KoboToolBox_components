<?php

namespace App\Http\Controllers\Admin;

use Alert;
use App\Http\Requests\XlsformRequest as StoreRequest;
use App\Http\Requests\XlsformRequest as UpdateRequest;
use App\Jobs\ArchiveKoboForm;
use App\Jobs\DeployFormToKobo;
use App\Jobs\MediaFiles\GenerateCsvLookupFiles;
use App\Jobs\GetDataFromKobo;
use App\Jobs\MediaFiles\UpsloadCsvMediaFileAttachementsToKoboForm;
use App\Models\ProjectXlsform;
use App\Models\Xlsform;
use Backpack\CRUD\CrudPanel;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Support\Facades\Storage;

/**
 * Class XlsformCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class XlsformCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        CRUD::setModel('App\Models\Xlsform');
        CRUD::setRoute(config('backpack.base.route_prefix') . '/xlsform');
        CRUD::setEntityNameStrings('xlsform', 'xlsforms');
    }

    protected function setupListOperation()
    {
        $this->crud->setColumns([
            [
                'name' => 'title',
                'label' => 'Form Title',
                'type' => 'text',
            ],
            [
                'name' => 'version',
                'label' => 'Version - Uploaded',
                'type' => 'date',
            ],
            [
                'name' => 'kobo_id',
                'label' => 'View on Kobotools',
                'type' => 'closure',
                'function' => function ($entry) {
                    if ($entry->kobo_id) {
                        return "<a target='_blank' href='https://kf.kobotoolbox.org/#/forms/".$entry->kobo_id."'>Kobotoolbox Link</a>";
                    }
                    return "<span class='text-secondary'>Not Deployed</span>";
                },
            ],
            [
                'name' => 'live',
                'label' => 'Is Form Available to Projects?',
                'type' => 'boolean',
            ],
            [
                'name' => 'link_page',
                'label' => 'Associated Guide(s)',
                'type' => "closure",
                'function' => function ($entry) {
                    $page = $entry->link_page;
                    return '<a href="'.url(''.$page.'').'" target="_blank">'.$page.'</a>';
                },

            ],
            [
                'name' => 'media',
                'label' => 'Attached Media Files',
                'type' => 'text',
            ],
            [
                'name' => 'csv_lookups',
                'label' => 'CSV Media From Database'
            ]
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->crud->addFields([

            [
                'name' => 'title',
                'type' => 'text',
                'label' => 'Choose a title for the downloads page',
            ],
            [
                'name' => 'xlsfile',
                'type' => 'upload',
                'upload' => true,
                'disk' => 'public' ,
                'label' => 'Upload the XLS Form file',
            ],
            [
                'name' => 'live',
                'label' => 'Is Form Available to Projects?',
                'type' => 'boolean',
            ],
            [
                'name' => 'link_page',
                'type' => 'url',
                'label' => 'Add the url to the online guide for this form',
            ],
            [   // CKEditor
                'name' => 'description',
                'type' => 'simplemde',
                'label' => 'Add a description for the form',
            ],
            [
                'name' => 'media',
                'label' => 'Upload any csv or image files required by the ODK form',
                'description' => 'These are the static files that should be uploaded as media file attachments for this ODK form',
                'type' => 'upload_multiple',
                'upload' => true,
                'disk' => 'public',
            ],
            [
                'name' => 'csv_lookups',
                'label' => 'Add MySQL Views that should be converted to CSV files and added as media file attachments for this ODK form',
                'description' => 'These are csv lookup files that need to be updated with new data as the database updates',
                'type' => 'repeatable',
                'fields' => [
                    [
                        'name' => 'mysql_view',
                        'label' => 'Name of MySQL View',
                    ],
                    [
                        'name' => 'csv_file',
                        'label' => 'Name of CSV File (without .csv extension)',
                    ],
                ],
            ],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function setupShowOperation()
    {
        $this->crud->set('show.setFromDb', false);

        Crud::button('deploy')
        ->stack('line')
        ->view('crud::buttons.deploy');

        Crud::button('sync')
        ->stack('line')
        ->view('crud::buttons.sync');

        Crud::button('archive')
        ->stack('line')
        ->view('crud::buttons.archive');

        Crud::button('csv_generate')
        ->stack('line')
        ->view('crud::buttons.csv_generate');

        $form = $this->crud->getCurrentEntry();

        Widget::add([
            'type' => 'view',
            'view' => 'crud::widgets.xlsform_kobo_info',
            'form' => $form,
        ])->to('after_content');

        $this->crud->addColumns([
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text'
            ],
            [
                'name' => 'link_page',
                'label' => 'Guide for this Form',
                'type' => 'text',
                'wrapper' => [
                    'href' => function ($crud, $column, $entry, $related_key) {
                        return $entry->link_page;
                    },
                    'target' => "_blank",
                ]
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea'
            ],
            [
                'name' => 'xlsfile',
                'label' => 'XLS Form File',
                'type' => 'upload',
                'limit' => 1000,
                'wrapper' => [
                    'href' => function ($crud, $column, $entry, $related_key) {
                        return Storage::disk('public')->url($entry->xlsfile);
                    },
                ]
            ],
            [
                'name' => 'media',
                'label' => 'Attached Media files (csv / images)',
                'type' => 'upload_multiple'
            ],
        ]);
    }


    public function deployToKobo(Xlsform $xlsform)
    {
        DeployFormToKobo::dispatch(backpack_auth()->user(), $xlsform);

        return response()->json([
            'title' => $xlsform->title,
            'user' => backpack_auth()->user()->email,
        ]);
    }

    public function syncData(Xlsform $xlsform)
    {
        GetDataFromKobo::dispatchNow(backpack_auth()->user(), $xlsform);

        $submissions = $xlsform->submissions;

        return $submissions->toJson();
    }

    public function archiveOnKobo(Xlsform $xlsform)
    {
        ArchiveKoboForm::dispatch(backpack_auth()->user(), $xlsform);

        return response()->json([
            'title' => $xlsform->title,
            'user' => backpack_auth()->user()->email,
        ]);
    }

    public function regenerateCsvFileAttachments(Xlsform $xlsform)
    {


        return response('files generating; check the logs in a few minutes to confirm success');
    }
}
