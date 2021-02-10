<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Exception;
use Prologue\Alerts\Facades\Alert;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessDataExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $query;
    protected $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($query, $params)
    {
        $this->query = $query;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {   
        $scriptName = 'save_data_csv.py';
        $scriptPath = base_path() . '/scripts/' . $scriptName;
        $db_user = config('database.connections.mysql.username');
        $db_password = config('database.connections.mysql.password');
        $db_name = config('database.connections.mysql.database');
        $base_path = base_path();
        $query = $this->query;
        $params = join(",",$this->params);
        $query = '"'.$query.'"';
        $params = '"'.$params.'"';
        
        //python script accepts 4 arguments in this order: base_path(), query, params and file name
      
        $process = new Process("python {$scriptPath} {$db_user} {$db_password} {$db_name} {$base_path} {$query} {$params}");

        $process->run();
        
        
        if(!$process->isSuccessful()) {
            
           throw new ProcessFailedException($process);
        
        } 
        Log::info("python done.");
        Log::info($process->getOutput());
       
    }

    public function failed(ProcessFailedException $exception)
    {
        return response()->$exception->getMessage();

    }
}
