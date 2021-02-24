<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\TeamMember;
use Illuminate\Support\Str;
use App\Http\Requests\TeamRequest;
use Illuminate\Support\Facades\Hash;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class TeamCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TeamCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Team::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/team');
        CRUD::setEntityNameStrings('team', 'teams');
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
                'name' => 'creator_id',
                'label' => 'Creator of team',
                'type' => 'select',
                'entity'    => 'user', 
                'model'     => "App\Models\User", 
                'attribute' => 'name',
            ],
            [
                'name' => 'name',
                'label' => 'Name',
                'type' => 'text',
            ],
            [
                'name' => 'description',
                'type' => 'text',
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'type' => 'select_from_array',
                'options' => ['1' => 'Active', '2' => 'Suspended', '3' => 'Canceled'],
            ],
            [   
                'name'      => 'image',
                'label'     => 'Image',
                'type'      => 'upload',
                'upload'    => true,
                'disk'      => 'public', 
            ],
            [   
                'name'  => 'privacy',
                'label' => 'Privacy',
                'type'  => 'check',
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
        CRUD::setValidation(TeamRequest::class);


        $this->crud->addFields([
            [
                'name' => 'creator_id',
                'label' => 'Who is creating this team?',
                'type' => 'select',
                'entity'    => 'user', 
                'model'     => "App\Models\User", 
                'attribute' => 'name',
                'default' => backpack_user()->id,
            ],
            [
                'name' => 'name',
                'label' => 'Insert the name for this team.',
                'type' => 'text',
            ],
            [
                'name' => 'description',
                'label' => 'Insert the description for this team.',
                'type' => 'textarea',
            ],
            [
                'name' => 'status',
                'label' => 'Select the status of team',
                'type' => 'select_from_array',
                'options' => ['1' => 'Active', '2' => 'Suspended', '3' => 'Canceled'],
                'allows_null' => false,
                'default'  => '1',
            ],
            [   
                'name'      => 'image',
                'label'     => 'Image',
                'type'      => 'upload',
                'upload'    => true,
                'disk'      => 'public', 
            ],
            [   
                'name'  => 'privacy',
                'label' => 'Do you would like to have this team privacy?',
                'type'  => 'checkbox',
                'default' => 1
            ],
            [   // repeatable
                'name'  => 'members',
                'label' => 'Members',
                'type'  => 'repeatable',
                'fields' => [
                    [
                        'name'    => 'name',
                        'type'    => 'text',
                        'label'   => 'Name',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name'    => 'email',
                        'type'    => 'email',
                        'label'   => 'Email',
                        'wrapper' => ['class' => 'form-group col-md-6'],
                    ],
                    [
                        'name'    => 'is_admin',
                        'type'    => 'checkbox',
                        'label'   => 'Is this members an admin?',
                        'hint'    => 'An admin can delete, edit the team and invite new members to the team.',
                        'wrapper' => ['class' => 'form-group'],
                    ],
                ],
            
                // optional
                'new_item_label'  => 'Add Member', 
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

    public function store()
    {
        $request = $this->crud->getRequest();
        $request->request->add(['slug'=> Str::slug($this->crud->getRequest()->name, '-')]);
        $response = $this->traitStore();
        $current_entry = $this->crud->getCurrentEntry();
        

        $this->updateOrCreateInvite($request->members, $current_entry->id);
        // $this->updateOrCreateTeamMember($request->members, $current_entry->id);
        return $response;
    }

    public function update()
    {
        $request = $this->crud->getRequest();
        
        
        $request->request->add(['slug'=> Str::slug($this->crud->getRequest()->name, '-')]);
        $response = $this->traitStore();
        $this->updateOrCreateTeamMember($request->members, $request->id);
        $this->updateOrCreateInvite($request->members, $request->id);
        return $response;
    }

    public function createOrReturnUser($member_info)
    {
        $user = User::where('email',$member_info->email)->first();
        if ($user==null){
            $text_password = Str::random(8);
            $password = Hash::make($text_password);
            $user = User::updateOrCreate(
                [
                    'email' =>$member_info->email,
                ],
                [
                    'name' => $member_info->name,
                    'password' => $password,
                ]
            );
        }
        $user->save();
        return $user;
    }

    public function updateOrCreateTeamMember($repeat, $team_id)
    {
        $members_repeat = json_decode($repeat);

        foreach ($members_repeat as  $index => $member_info) {
            if (!empty($member_info->email)) {
                $user = $this->createOrReturnUser($member_info);
                $team_member = TeamMember::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'team_id' => $team_id,
                    ],
                    [
                        'is_admin' => $member_info->is_admin,
                    ]
                );
                $team_member->save();
            }
        }
    }
}
