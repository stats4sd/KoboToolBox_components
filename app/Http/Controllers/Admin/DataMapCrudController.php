<?php

namespace App\Http\Controllers\Admin;

use App\Models\Variable;
use App\Http\Requests\DataMapRequest;
use App\Http\Requests\DataMapStoreRequest;
use App\Http\Requests\DataMapUpdateRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DataMapCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DataMapCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation  { store as traitStore;  update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $this->crud->setModel('App\Models\DataMap');

        $this->crud->setRoute(config('backpack.base.route_prefix') . '/datamap');
        $this->crud->setEntityNameStrings('datamap', 'data_maps');
    }

    protected function setupListOperation()
    {
        // TODO: remove setFromDb() and manually define Columns, maybe Filters
        $this->crud->addColumns([
            [
                'name' => 'id',
                'label' => 'value',
            ],
            [
                'name' => 'title',
                'label' => 'Title'
            ],
            [
                'name' => 'location',
                'label' => 'Location'
            ],
            [
                'name' => 'repeat_group',
                'label' => 'Repeat Group Name'
            ],
            [
                'type' => "relationship",
                'name' => 'xlsforms',
            ]
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->crud->setValidation(DataMapStoreRequest::class);

        $this->crud->addFields([
            [
                'name' => 'intro',
                'type' => 'custom_html',
                'value' => '<h4>Data Mapping - Form Module to SQL Table</h4>
                            <ul>
                            <li>The data map here tells the platform how to store the data coming from ODK.
                            <li>Each module of the ODK form must have a data map, which corresponds to a single MySQL table.</li>
                            <li>All variables to be stored in the table should be listed below.</li>
                            </ul>',
            ],
            [
                'type' => "relationship",
                'name' => 'xlsforms',
            ],
            [
                'name' => 'id',
                'type' => 'text',
                'label' => 'Give the ID of the datamap. This should be the same as the name of the module in the modules choice list for the "modulos" or "modulos_cultivo" questions in the ODK form',
            ],
            [
                'name' => 'title',
                'type' => 'text',
                'label' => 'Give the title / label for the data map',
            ],
            [
                'name' => 'location',
                'type' => 'boolean',
                'hint' => 'The ODK variable name should be `location`',
                'label' => 'Does this data map include a `location` field? (Note, this will probably be NO for every module except Parcela...)',
            ],
            [
                'name' => 'repeat_group',
                'label' => 'Name of the repeat group',
            ],
            [
                'name' => 'variables',
                'type' => 'repeatable',
                'label' => 'Add the other variables the data map should look for in the ODK Form',
                'fields' => [
                    [
                        'name' => 'model',
                        'label' => 'Model',
                        'type' => 'text',
                        
                    ],
                    [
                        'name' => 'xlsform_varname',
                        'label' => 'ODK Variable Name',
                        'hint' => 'This should be taken from the "name" column of the XLSForm',
                        'type' => 'text',
                        'wrapper' => ['class' => 'form-group col-md-4'],
                    ],
                    [
                        'name' => 'db_varname',
                        'label' => 'MySQL Column Name',
                        'type' => 'text',
                        'wrapper' => ['class' => 'form-group col-md-4'],
                    ],
                    [
                        'name' => 'type',
                        'label' => 'Select the variable type',
                        'type' => 'select2_from_array',
                        'wrapper' => ['class' => 'form-group col-md-4'],
                        'options' => [
                            'boolean' => 'Boolean (select_one with yes/no or 1/0 options)',
                            'text' => 'text / select_one',
                            'integer' => 'integer',
                            'decimal' => 'decimal',
                            'select_multiple' => 'select_multiple',
                            'date' => 'date',
                            'timestamp' => 'datetime',
                            'gps' => 'geopoint',
                            'photo' => 'photo',
                        ],
                    ],
                    [
                        'name' => 'json',
                        'label' => 'Check only if the variable is a json type',
                        'type' => 'checkbox',
                        'value' => 0,
                    ],
                    [
                        'name' => 'in_db',
                        'label' => 'Check only if the variable is confirmed to be in the database.',
                        'hint' => 'If this is checked and the variable is not found in MySQL, it will break the importer! So if in doubt, do not tick!',
                        'type' => 'checkbox',
                        'value' => 0,
                    ],
                    [
                        'name' => 'linked_other',
                        'label' => 'the variable name that contains the "other" answer',
                        'type' => 'text',
                        
                    ],
                ],
            ],
        ]);
    }

    protected function store(DataMapRequest $request)
    {
        $response = $this->traitStore();
        // do something after save
        $datamap = $this->crud->getCurrentEntry();

       $variables = json_decode($request->variables);

        foreach ($variables as  $variable) {
            if (!empty($variable)) {
                Variable::create(
                    [
                        'data_map_id' => $datamap->id,
                        'xlsform_varname' => $variable->xlsform_varname,
                        'db_varname' => $variable->db_varname,
                        'in_db' => $variable->in_db,
                        'type' => $variable->type,
                        'model' => $variable->model,
                        'json' => $variable->json,
                        'linked_other' => $variable->linked_other,
                    ]
                );
            }
        }
        return $response;
    }

    protected function update(DataMapRequest $request)
    {
        $response = $this->traitUpdate();
        // do something after save
        $datamap = $this->crud->getCurrentEntry();

       $variables = json_decode($request->variables);

        foreach ($variables as  $variable) {
            if (!empty($variable)) {
                Variable::create(
                    [
                        'data_map_id' => $datamap->id,
                        'xlsform_varname' => $variable->xlsform_varname,
                        'db_varname' => $variable->db_varname,
                        'in_db' => $variable->in_db,
                        'type' => $variable->type,
                        'model' => $variable->model,
                        'json' => $variable->json,
                        'linked_other' => $variable->linked_other,
                    ]
                );
            }
        }
        return $response;
    }


    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
        $this->crud->modifyField('id', [
            'attributes' => [
                'readonly' => true,
            ],
        ]);
        $this->crud->setValidation(DataMapUpdateRequest::class);
    }


    protected function setupShowOperation()
    {
        $this->crud->addColumns([
            [
                'name' => 'id',
                'label' => 'value',
            ],
            [
                'name' => 'title',
                'label' => 'label'
            ],
            [
                'name' => 'location',
                'type' => 'boolean',
                'label' => 'Does this data map include a `location` field?',
            ],
            [
                'name' => 'variables',
                'label' => 'Variables',
                'type' => 'table',
                'columns' => [
                    'xlsform_varname' => 'ODK Variable Name',
                    'db_varname' => 'MySQL Column Name',
                    'model' => 'Model',
                    'type' => 'Type',
                    'json' => 'Is a json type?',
                    'in_db' => 'Is the variable present in the database?',
                    'linked_other' => 'variable name for Other'
                ],
            ]
        ]);
    }
}
