<?php

namespace App\Http\Controllers\Admin;

use App\Models\Xlsform;
use App\Models\TeamXlsform;
use App\Jobs\ArchiveKoboForm;
use App\Jobs\GetDataFromKobo;
use App\Jobs\DeployFormToKobo;
use Backpack\CRUD\app\Library\Widget;
use App\Http\Requests\TeamXlsformRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class TeamXlsformCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TeamXlsformCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\TeamXlsform::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/teamxlsform');
        CRUD::setEntityNameStrings('teamxlsform', 'team_xlsforms');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // columns

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(TeamXlsformRequest::class);

        CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
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
                'name' => 'xlsform',
                'label' => 'XLS Form File',
                'type' => 'relationship',
            ],
        ]);
    }


    public function deployToKobo(TeamXlsform $team_xlsform)
    {
        DeployFormToKobo::dispatch(backpack_auth()->user(), $team_xlsform);

        return response()->json([
            'title' => $team_xlsform->title,
            'user' => backpack_auth()->user()->email,
        ]);
    }

    public function syncData(TeamXlsform $team_xlsform)
    {
        GetDataFromKobo::dispatchNow(backpack_auth()->user(), $team_xlsform);

        $submissions = $team_xlsform->team_submissions;

        return $submissions->toJson();
    }

    public function archiveOnKobo(TeamXlsform $team_xlsform)
    {
        ArchiveKoboForm::dispatch(backpack_auth()->user(), $team_xlsform);

        return response()->json([
            'title' => $team_xlsform->title,
            'user' => backpack_auth()->user()->email,
        ]);
    }

    public function regenerateCsvFileAttachments(TeamXlsform $team_xlsform)
    {
        return response('files generating; check the logs in a few minutes to confirm success');
    }
}


