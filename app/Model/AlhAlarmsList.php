<?php

namespace App\Model;

use App\Exceptions\AlhException;
use Illuminate\Support\Collection;

class AlhAlarmsList
{

    function __construct(protected Collection $alarms = new Collection)
    {
    }

    /**
     * Get the list of source files that contain ALH definitions.
     *
     */
    function files(): array
    {
        $files = [];
        foreach (scandir($this->alarmsDir()) as $item) {
            if ($item == 'classes'){
                continue;  // it doesn't contain alarms
            }
            $path = $this->alarmsDir() . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $this->assertFileIsReadable($path);
                $files[] = $path;
            }
        }
        return $files;
    }

    protected function alarmsDir(){
        return config('settings.alh_source_directory'). DIRECTORY_SEPARATOR .'alarms';
    }

    protected function getAlarms(): Collection
    {
        if ($this->alarms->isEmpty()) {
            foreach ($this->files() as $file) {
                $this->read($file);
            }
            $this->alarms = $this->alarms->mapWithKeys(function ($item, int $key) {
                return [$item->source->{'org.jlab.jaws.entity.EPICSSource'}->pv => $item];
            });
        }
        return $this->alarms;
    }

    public function alarms(): Collection
    {
        return $this->getAlarms();
    }


    public function distinctActionNames(){
        return $this->alarms()->pluck('action')->unique()->sort();
    }

    /**
     * Read and parse the content of an ALH data file
     */
    protected function read(string $file)
    {
        $lineNum = 0;
        foreach (file($file) as $line) {
            $lineNum++;
            try {
                $scrubbedLine = preg_replace('/[\x00-\x1F\x7F]/','', $line);
                if (preg_match("/^(.*)=(\{.*)$/", $scrubbedLine, $m)) {
                    $name = $m[1];
                    $data = $m[2];
                    $alarm = json_decode($data, false);
                    if (!$alarm) {
                        throw new AlhException("failed to parse line $lineNum of $file \n");
                    }
                    $alarm->name = trim($name);
                    $alarm->pv = $this->extractPv($alarm);
                    $alarm->managedby = 'ALH:'.basename($file);
                    $alarm->action = trim($alarm->action);
                    $this->alarms->push($alarm);
                }
            } catch (AlhException $e) {
                print $e->getMessage();
                //TODO - we should throw to abort processing if we can't read ALH cleanly
            }
        }
    }

    /**
     * Dig the pv field out of its nested path
     */
    protected function extractPv($item)
    {
        if (isset($item->source)) {
            $key = "org.jlab.jaws.entity.EPICSSource";
            if (isset($item->source->{$key})) {
                if (isset($item->source->{$key}->pv)) {
                    return $item->source->{$key}->pv;
                }
            }
        }
        return null;
    }

    protected function assertFileIsReadable($file)
    {
        if (!is_readable($file)) {
            throw new \Exception("Source file $file is not readable");
        }
    }

}
