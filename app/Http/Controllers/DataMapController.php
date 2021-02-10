<?php

namespace App\Http\Controllers;

use App\Models\DataMap;
use App\Models\Parcela;
use App\Models\Comunidad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Events\NewDataVariableSpotted;
use App\Jobs\ImportAttachmentFromKobo;

class DataMapController extends Controller
{
    public static function newRecord(DataMap $dataMap, array $data)
    {
        $variables = $dataMap->variables->toArray();
        $newItem = DataMapController::makeNewRecord($variables, $data);
        return $newItem;
    
    }

    public static function makeNewRecord(array $variables, array $data)
    {
        $newModel = DataMapController::createNewModel($variables, $data);
      
        $class = 'App\\Models\\'.$variables[0]['model'];
        $newItem = new $class();
        $newItem->fill($newModel);

        $newItem->save();
        return $newItem;
    }

    //update exists plot
    public static function updateRecord(array $variables, array $data, $id)
    {
        $model = DataMapController::createNewModel($variables, $data);
        $class = 'App\\Models\\'.$variables[0]['model'];
        $class::where('id', $id)->update($model);
        return $model;
    }

    public static function createNewModel(array $variables, array $data)
    {
        //add the submission_id
        $newModel = [
            "submission_id" => $data['_id']
        ];

        $dataMap = DataMap::find($variables[0]['data_map_id']);

        // split the gps coordinates into longitude, latitude, altitude and accuracy
        if ($dataMap->location && isset($data['gps']) && $data['gps']) {
            $location = explode(" ", $data['gps']);
            $newModel["longitude"] = isset($location[1]) ? $location[1] : null;
            $newModel["latitude"] = isset($location[0]) ? $location[0] : null;
            $newModel["altitude"] = isset($location[2]) ? $location[2] : null;
            $newModel["accuracy"] = isset($location[3]) ? $location[3] : null;
        }

        foreach ($variables as $variable) {
            // if the variable is new (i.e. hasn't been manually added to the database)
            if ($variable['in_db'] == 0) {
                //don't actually process it (as the SQL Insert will fail)
                //just tell the admin about it!
                NewDataVariableSpotted::dispatch($variable, $dataMap);
                continue;
            }

            $variableName = $variable['xlsform_varname'];
            $value = null;

            switch ($variable['type']) {
                case 'boolean':
                    if (isset($data[$variableName]) && $data[$variableName]) {
                        switch ($data[$variableName]) {
                            case 'yes':
                                $value = 1;
                            break;

                            case 'no':
                                $value = 0;
                            break;

                            case "1":
                            case "0":
                                $value = $data[$variableName];
                            break;
                            // error handling in a painfully basic way - set any unhandled values to null;
                            default:
                                $value = null;
                            break;
                        }
                    }
                break;

                case 'photo':
                    if (isset($data[$variableName]) && $data[$variableName]) {
                        $value = $data[$variableName];
                        ImportAttachmentFromKobo::dispatch($value, $data);
                    }
                break;

                case 'date':
                    if (isset($data[$variableName]) && $data[$variableName]) {
                        $value = Carbon::parse($data[$variableName]);
                        $value = $value->toDateString();
                    }
                break;

                case 'datetime':
                    if (isset($data[$variableName]) && $data[$variableName]) {
                        $value = Carbon::parse($data[$variableName]);
                        $value = $value->toDateTimeString();
                    }
                break;

                case 'select_multiple':
                case 'geopoint':
                    $value = null;
                break;

                default:
                    $value = isset($data[$variableName]) ? $data[$variableName] : null;
                break;
            }

            if (!is_null($value)) {
                //look the column name that matches to the variable name from the survey
                $newModel[$variable['db_varname']] = $value;
                // $newModel['model'] = $variable['model'];
            }
        }

        return $newModel;
    }
}
