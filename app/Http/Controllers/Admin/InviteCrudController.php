<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Mail\InviteUser;
use Illuminate\Support\Str;
use App\Http\Requests\InviteRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class InviteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InviteCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
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
        CRUD::setModel(\App\Models\Invite::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invite');
        CRUD::setEntityNameStrings('invite', 'invites');
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
        CRUD::setValidation(InviteRequest::class);

        CRUD::addFields([
            [
                'name' => 'team_id',
                'label' => 'Select team',
                'type' => 'select',
                'entity'    => 'team', 
                'model'     => "App\Models\Team", 
                'attribute' => 'name',
            ],
          
            [
                'name' => 'inviter_id',
                'label' => 'Who inviter the user',
                'type' => 'select',
                'entity'    => 'user', 
                'model'     => "App\Models\User", 
                'attribute' => 'name',
                'default' => backpack_user()->id,
            ],
            [
                'name' => 'token',
                'type' => 'hidden',
                'value' => Str::random(60),
            ],
            [
                'name' => 'name',
                'type' => 'text',
            ],
            [
                'name' => 'email',
                'type' => 'email',
            ]
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

    public function store(InviteRequest $request)
    {
        // do something before validation, before save, before everything
        $response = $this->traitStore();
        $text_password = Str::random(8);
        $password = Hash::make($text_password);
  
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password'=> $password,
        ]);

        
        $user->save();
        //$user->teams()->sync($request['team_id']); 
        
        Mail::to($request['email'])->send(new InviteUser($user, $text_password));
        // do something after save
        return $response;

    }
}
