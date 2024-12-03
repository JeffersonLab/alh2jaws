<?php

namespace App\Service;

use Illuminate\Support\Collection;

class Comparison
{

    // Map of corrections for the names used in Michele's files that must be
    // transformed to match the database. (ex: "Hall A" => 'HallA')
    const LOCATION_CORRECTIONS = [
        'Hall A'  => 'HallA',
        'Hall B'  => 'HallB',
        'Hall C'  => 'HallC',
        'Hall D'  => 'HallD',
        'ACC'     => 'CEBAF',
        'FEL'     => 'LERF',
    ];

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

    public static function correctedLocationName($name): string{
        if (array_key_exists($name, self::LOCATION_CORRECTIONS)){
            return self::LOCATION_CORRECTIONS[$name];
        }
        return $name;
    }

    public function notInJaws()
    {
        return $this->alhAlarms->filter(function ($item, $key) {
            return !$this->jawsAlarms->has($key);
        });
    }

    public function notInAlh()
    {
        return $this->jawsAlarms->filter(function ($item, $key) {
            return ! $this->alhAlarms->has($key);
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

    public function differs($jawsItem, $alhItem): bool{
        return ! $this->matches($jawsItem, $alhItem);
    }

    public function matches($jawsItem, $alhItem): bool{
        return $this->compareAction($jawsItem, $alhItem)
            && $this->compareName($jawsItem, $alhItem)
            && $this->compareScreenCommands($jawsItem, $alhItem)
            && $this->compareManagedBy($jawsItem, $alhItem)
            && $this->compareMaskedBy($jawsItem, $alhItem)
            && $this->compareLocations($jawsItem, $alhItem);
    }

    protected function compare($jawsItem, $alhItem)
    {
        // Our compare functions will be named after the key as it
        // exists in JAWS.
        $this->compareAction($jawsItem, $alhItem);
        $this->compareName($jawsItem, $alhItem);
        $this->compareScreenCommands($jawsItem, $alhItem);
        $this->compareManagedBy($jawsItem, $alhItem);
        $this->compareMaskedBy($jawsItem, $alhItem);
        $this->compareLocations($jawsItem, $alhItem);
    }

    /**
     * Identify differences between Jaws Action match the ALH Action
     * Returns false on mismatch
     */
    protected function compareAction($jawsItem, $alhItem) : bool
    {
        if ($jawsItem->action->name != $alhItem->action){
            $key = "Action Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsItem->action->name} / {$alhItem->action}";
            $this->pushDifference($key, $message);
            return false;
        }
        return true;
    }

    /**
     * Identify differences between Jaws Action match the ALH Action
     * Returns false on mismatch
     */
    protected function compareName($jawsItem, $alhItem) : bool
    {
        if ($jawsItem->name != $alhItem->name){
            $key = "Name Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsItem->name} / {$alhItem->name}";
            $this->pushDifference($key, $message);
            return false;
        }
        return true;
    }

    protected function comparePvs($jawsItem, $alhItem): bool
    {
        $jawsPv = $jawsItem->pv ?? null;
        $alhPv = $alhItem->pv;
        if ($jawsPv != $alhPv){
            $key = "PV Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsPv} / {$alhPv}";
            $this->pushDifference($key, $message);
            return false;
        }
        return true;
    }

    protected function compareScreenCommands($jawsItem, $alhItem): bool
    {
        $jawsVal = $jawsItem->screenCommand ?? null;
        $alhVal = $alhItem->screencommand ?? null;
        if ($jawsVal != $alhVal){
            $key = "Screen Command Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsVal} / {$alhVal}";
            $this->pushDifference($key, $message);
            return false;
        }
        return true;
    }

    protected function compareMaskedBy($jawsItem, $alhItem): bool
    {
        $jawsVal = $jawsItem->maskedBy ?? null;
        $alhVal = $alhItem->maskedby ?? null;  //alh does not use camelcase here
        if ($jawsVal != $alhVal){
            $key = "MaskedBy Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsVal} / {$alhVal}";
            $this->pushDifference($key, $message);
            return false;
        }
        return true;
    }

    protected function compareManagedBy($jawsItem, $alhItem): bool
    {
        $jawsVal = $jawsItem->managedBy ?? null;
        $alhVal = $alhItem->managedby ?? null;  //alh does not use camelcase here
        if ($jawsVal != $alhVal){
            $key = "ManagedBy Mismatch: ". $jawsItem->name . ': ';
            $message = "{$jawsVal} / {$alhVal}";
            $this->pushDifference($key, $message);

            return false;
        }
        return true;
    }


    protected function compareLocations($jawsItem, $alhItem) : bool
    {
        $jawsVal = collect($jawsItem->locations)->pluck('name')->sort();

        $alhVal = collect($alhItem->location);  //alh is in fact singular
        $alhVal = $alhVal->map(function($item){
           return static::correctedLocationName($item);
        })->sort();

        if (array_values($jawsVal->toArray()) != array_values($alhVal->toArray())){
            $key = "Location Mismatch: ". $jawsItem->name . ': ';
            $message = implode(',',$jawsVal->toArray()) .' / ' . implode(',', $alhVal->toArray());
            $this->pushDifference($key, $message);
            return false;
        }
        return true;
    }




    protected function pushDifference($key, $message){
        $messages = $this->differences->get($key) ?: [];
        $messages[]  = $message;
        $this->differences->put($key, $messages);
    }


}
