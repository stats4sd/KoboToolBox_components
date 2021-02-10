<?php

namespace App\Jobs\MediaFiles;

use App\Models\Xlsform;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Version of the uploadMediaFileAttachments job that ONLY handles csv files. All non .csv files are ignored. Use this to avoid replacing lots of large image / multimedia files on Kobotools.
 * @param Xlsform $xlsform
 */
class UploadCsvMediaFileAttachementsToKoboForm implements ShouldQueue
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
            if ($metadata['data_type'] === "media" && $metadata['data_file_type'] === "text/csv") {
                Http::withBasicAuth(config('services.kobo.username'), config('services.kobo.password'))
                ->delete(config('services.kobo.old_endpoint').'/api/v1/metadata/'.$metadata['id'])
                ->throw();
            }
        }

        foreach ($this->form->media as $media) {

            // if the file is not a csv, ignore it
            if (!Str::endsWith($media, 'csv')) {
                continue;
            }

            UploadFileToKoboForm::dispatch($media, $koboform);
        }

        foreach ($this->form->csv_lookups as $csvMedia) {
            UploadFileToKoboForm::dispatch($csvMedia['csv_file'].'.csv', $koboform);
        }
    }
}
