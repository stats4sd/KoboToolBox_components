<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TeamMemberRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class TeamMemberCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TeamMemberCrudController extends CrudController
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
        CRUD::setModel(\App\Models\TeamMember::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/team_member');
        CRUD::setEntityNameStrings('team member', 'team members');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addColumns([
            [
                'name' => 'team',
                'label' => 'Team Name',
                'type' => 'relationship',
            ],
            [
                'name' => 'user',
                'label' => 'User',
                'type' => 'relationship',
            ],
            [
                'name' => 'is_admin',
                'label' => 'Is admin',
                'type' => 'check',
            ],
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(TeamMemberRequest::class);

        $this->crud->addFields([
            [
                'name' => 'team',
                'label' => 'Team Name',
                'type' => 'relationship',
            ],
            [
                'name' => 'user',
                'label' => 'User',
                'type' => 'relationship',
            ],
            [
                'name' => 'is_admin',
                'label' => 'Is admin',
                'type' => 'checkbox',
            ],
        ]);
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
}
