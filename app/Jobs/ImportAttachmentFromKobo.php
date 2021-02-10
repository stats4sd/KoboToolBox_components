<?php

namespace App\Jobs;

use Psy\Util\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ImportAttachmentFromKobo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $name;

    public $submission;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(String $name, Array $submission)
    {
        //
        $this->name = $name;
        $this->submission = $submission;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // As of June 11 2020, downloadable filename is in the format:
        // kobo_username/attachments/submission['formhub/uuid']/submission['_uuid']/submission['photo_variable']
       
        $filename = 'bolivia_agromet/attachments/'. $this->submission['formhub/uuid'].'/'.$this->submission['_uuid'].'/'.$this->name;

        $downloadUrl = 'https://kc.kobotoolbox.org/media/original?media_file='.urlencode($filename);
    
        $response = Http::withBasicAuth(config('services.kobo.username'),config('services.kobo.password'))
        ->get($downloadUrl)
        ->throw();

        // store file in "attachments / _id / name"
        $store_media = Storage::disk('media')->put($this->submission['_id'].'/'.$this->name, $response);




    }
}
