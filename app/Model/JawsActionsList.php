<?php

namespace App\Model;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class JawsActionsList
{
    function __construct(protected Collection $actions = new Collection){
    }

    function url(){
        return config('settings.jaws_api_base') . '/list-actions';
    }

    protected function getActions() : Collection{
        if ($this->actions->isEmpty()){
            $response = Http::withOptions([
                'debug' => false,
            ])->get($this->url());
            $this->actions = $response->collect('list');
        }
        return $this->actions;
    }

    public function actions(): Collection{
        return $this->getActions();
    }

    public function distinctActionNames(){
        return $this->actions()->pluck('name')->unique()->sort();
    }
}
