<?php

namespace App\Jobs\MediaFiles;

use App\Models\Xlsform;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Symfony\Component\Process\Exception\ProcessFailedException;

/**
 * Job to generate the csv files from the specified mysql tables/views. Generates all the csv files required for the xlsform passed to it, as defined in the xlsform->csv_lookups property
 * @param Xlsform $xlsform
 */
class GenerateCsvLookupFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $xlsform;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Xlsform $xlsform)
    {
        $this->xlsform = $xlsform;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mediaToGenerate = $this->xlsform->csv_lookups;

        foreach ($mediaToGenerate as $media) {
            $scriptPath = base_path().'/scripts/save_table.py';

            $process = new Process(['pipenv', 'run', 'python3', $scriptPath, $media['mysql_view'],  $media['csv_file']]);
            $process = $process->setWorkingDirectory(base_path());

            $process->run();
            Log::info('generating file: '.$media['csv_file'].' from mysql view: '.$media['mysql_view']);
            Log::info($process->getOutput());

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }
    }
}
