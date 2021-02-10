<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\DataMap;
use App\Models\Xlsform;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\DataMapController;
use App\Models\TeamSubmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GetDataFromKobo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $form;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, Xlsform $form)
    {
        $this->form = $form;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = Http::withBasicAuth(config('services.kobo.username'), config('services.kobo.password'))
        ->withHeaders(['Accept' => 'application/json'])
        ->get(config('services.kobo.endpoint_v2').'/assets/'.$this->form->kobo_id.'/data/')
        ->throw()
        ->json();

        Log::info($response);

        $data = $response['results'];

        //compare
        $submissions = TeamSubmission::where('xlsform_id', '=', $this->form->id)->get();

        foreach ($data as $newSubmission) {
            if (!in_array($newSubmission['_id'], $submissions->pluck('id')->toArray())) {
                TeamSubmission::create([
                    'id' => $newSubmission['_id'],
                    'uuid' => $newSubmission['_uuid'],
                    'xlsform_id' => $this->form->id,
                    'content' => json_encode($newSubmission),
                    'date_time' => $newSubmission['_submission_time'],
                ]);
               
                $newSubmission = $this->deleteGroupName($newSubmission);

                $xls_form = Xlsform::findOrfail($this->form->id);

                foreach ($xls_form->datamaps as $dataMap) {
                    
                    DataMapController::newRecord($dataMap, $newSubmission);
                    
                }
                if(array_key_exists('modulos', $newSubmission)){

                    $newSubmission['modulos'] = explode(' ', $newSubmission['modulos']);
    
                    foreach ($newSubmission['modulos'] as $modulo) {
                        $dataMap = DataMap::findOrfail($modulo);
                        DataMapController::newRecord($dataMap, $newSubmission);
                    }
                }
            }
          
            

        } // end foreach record
    } // end handle method

    public function deleteGroupName(array $array)
    {
        foreach ($array as $key => $value) {
            if (Str::contains($key, '/') && $key!="formhub/uuid") {
                // e.g. replace $newSubmission['groupname/subgroup/name'] with $newSubmission['name']
                unset($array[$key]);
                $key = explode('/', $key);
                $key = end($key);
                $array[$key] = $value;
            }
            if (is_array($value)) {
                $array[$key] = $this->deleteGroupName($value);
            }
        }
        return $array;
    }

   
    
}
