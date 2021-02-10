<?php

namespace App\Jobs\MediaFiles;

use App\Models\Xlsform;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Job to handle replacing / uploading ALL media files for the passed xlsform.
 *  - Deletes the old versions off Kobotoolbox
 *  - Takes the media and csv_lookup properties of the passed xlsform, and passes each file to the uploader
 * @param Xlsform $xlsform
 */
class UploadMediaFileAttachementsToKoboForm implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $form;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Xlsform $form)
    {
        //
        $this->form = $form;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // media upload still works on the OLD Kobo Api, so we need the OLD formid:

        $oldIdResponse = Http::withBasicAuth(config('services.kobo.username'), config('services.kobo.password'))
        ->withHeaders(['Accept' => 'application/json'])
        ->get(config('services.kobo.old_endpoint').'/api/v1/forms?id_string='.$this->form->kobo_id)
        ->throw()
        ->json();

        $koboform = $oldIdResponse[0];


        // delete any existing media from form to make way for fresh upload:
        foreach ($koboform['metadata'] as $metadata) {
            if ($metadata['data_type'] === "media") {
                Http::withBasicAuth(config('services.kobo.username'), config('services.kobo.password'))
                ->delete(config('services.kobo.old_endpoint').'/api/v1/metadata/'.$metadata['id'])
                ->throw();
            }
        }

        foreach ($this->form->media as $media) {
            UploadFileToKoboForm::dispatch($media, $koboform);
        }

        foreach ($this->form->csv_lookups as $csvMedia) {
            UploadFileToKoboForm::dispatch($csvMedia['csv_file'].'.csv', $koboform);
        }
    }
}
