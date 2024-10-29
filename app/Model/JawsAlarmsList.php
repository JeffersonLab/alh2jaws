<?php

namespace App\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class JawsAlarmsList
{
    function __construct(protected Collection $alarms = new Collection){
    }

    function url(){
        return config('settings.jaws_api_base') . '/list-alarms';
    }

    protected function getAlarms() : Collection{
        if ($this->alarms->isEmpty()){
            $response = Http::withOptions([
                'debug' => false,
            ])->get($this->url());
            foreach ($response->json()['list'] as $item){
                $json = json_encode($item);
                $object = json_decode($json, false);
                $this->alarms->push($object);
            }
            $this->alarms = $this->alarms->keyBy('pv');
        }
        return $this->alarms;
    }

    public function alarms(): Collection{
        return $this->getAlarms();
    }
}
