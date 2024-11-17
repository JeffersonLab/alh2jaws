<?php

namespace App\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class LocationsList
{
    function __construct(protected Collection $locations = new Collection){
    }

    function url(){
        return config('settings.jaws_api_base') . '/list-locations';
    }

    protected function getLocations() : Collection{
        if ($this->locations->isEmpty()){
            $response = Http::withOptions([
                'debug' => false,
            ])->get($this->url());
            $this->locations = $response->collect('list');
        }
        return $this->locations;
    }

    public function locations(): Collection{
        return $this->getLocations();
    }

    public function distinctLocationNames(){
        return $this->locations()->pluck('name')->unique()->sort();
    }
}
