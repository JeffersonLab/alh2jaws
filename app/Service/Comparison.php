<?php

namespace App\Service;

use Illuminate\Support\Collection;

class Comparison
{

    /**
     *  Collection keyed by alarm name.
     *  Each key has an array of message strings.
     *
     */
     protected Collection $differences;

    function __construct(protected Collection $alhAlarms,
                         protected Collection $jawsAlarms)
    {
        $this->differences = new Collection();
    }

    public function notInJaws()
    {
        return $this->alhAlarms->filter(function ($item) {
            return !$this->jawsAlarms->get($item->name);
        });
    }

    public function notInAlh()
    {
        return $this->jawsAlarms->filter(function ($item) {
            return !$this->alhAlarms->get($item->name);
        });
    }

    public function keysInCommon()
    {
        return $this->jawsAlarms->intersectByKeys($this->alhAlarms)->keys();
    }

    public function differences()
    {
        foreach ($this->keysInCommon() as $key) {
            $jawsItem = $this->jawsAlarms->get($key);
            $alhItem = $this->alhAlarms->get($key);
            $this->compare($jawsItem, $alhItem);
        }
        return $this->differences;
    }

    protected function compare($jawsItem, $alhItem)
    {
        // Our compare functions will be named after the key as it
        // exists in JAWS.
//        $this->compareAction($jawsItem, $alhItem);
//        $this->comparePvs($jawsItem, $alhItem);
//        $this->compareScreenCommands($jawsItem, $alhItem);
//        $this->compareMaskedBy($jawsItem, $alhItem);
        $this->compareLocations($jawsItem, $alhItem);
    }

    /**
     * Identify differences between Jaws Action match the ALH Class?
     */
    protected function compareAction($jawsItem, $alhItem)
    {
        if ($jawsItem->action->name != $alhItem->class){
            $key = "Action Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsItem->action->name} / {$alhItem->class}";
            $this->pushDifference($key, $message);
        }
    }

    protected function comparePvs($jawsItem, $alhItem)
    {
        $jawsPv = $jawsItem->pv ?? null;
        $alhPv = $this->alhPv($alhItem);
        if ($jawsPv != $alhPv){
            $key = "PV Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsPv} / {$alhPv}";
            $this->pushDifference($key, $message);
        }
    }

    protected function compareScreenCommands($jawsItem, $alhItem)
    {
        $jawsVal = $jawsItem->screenCommand ?? null;
        $alhVal = $alhItem->screenCommand ?? null;
        if ($jawsVal != $alhVal){
            $key = "Screen Command Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsVal} / {$alhVal}";
            $this->pushDifference($key, $message);
        }
    }

    protected function compareMaskedBy($jawsItem, $alhItem)
    {
        $jawsVal = $jawsItem->maskedBy ?? null;
        $alhVal = $alhItem->maskedby ?? null;  //alh does not use camelcase here
        if ($jawsVal != $alhVal){
            $key = "MaskedBy Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsVal} / {$alhVal}";
            $this->pushDifference($key, $message);
        }
    }

    protected function compareLocations($jawsItem, $alhItem)
    {
        $jawsVal = collect($jawsItem->locations)->pluck('name');
        var_dump($jawsVal);
        $alhVal = collect($alhItem->location);  //alh is in fact singular
        var_dump($alhVal);
//        if ($jawsVal != $alhVal){
//            $key = "MaskedBy Mismatch: ". $jawsItem->name . ': ';
//            $message = "{$jawsVal} / {$alhVal}";
//            $this->pushDifference($key, $message);
//        }
    }


    protected function alhPv($alhItem)
    {
        if (isset($alhItem->producer)) {
            $key = "org.jlab.jaws.entity.EPICSProducer";
            if (isset($alhItem->producer->{$key})) {
                if (isset($alhItem->producer->{$key}->pv)) {
                    return $alhItem->producer->{$key}->pv;
                }
            }
        }
        return null;
    }


    protected function pushDifference($key, $message){
        $messages = $this->differences->get($key) ?: [];
        $messages[]  = $message;
        $this->differences->put($key, $messages);
    }


}
