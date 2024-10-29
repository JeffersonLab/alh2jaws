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
        foreach (scandir(config('settings.alh_source_directory')) as $item) {
            if ($item == 'classes'){
                continue;  // it doesn't contain alarms
            }
            $path = config('settings.alh_source_directory') . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $this->assertFileIsReadable($path);
                $files[] = $path;
            }
        }
        return $files;
    }

    protected function alarmsDir(){

    }
    
    protected function getAlarms(): Collection
    {
        if ($this->alarms->isEmpty()) {
            foreach ($this->files() as $file) {
                $this->read($file);
            }
            $this->alarms = $this->alarms->keyBy('name');
        }
        return $this->alarms;
    }

    public function alarms(): Collection
    {
        return $this->getAlarms();
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
                if (preg_match("/^(.*)=(\{.*)$/", $line, $m)) {
                    $name = $m[1];
                    $data = $m[2];
                    $alarm = json_decode($data, false);
                    if (!$alarm) {
                        throw new AlhException("failed to parse line $lineNum of $file \n");
                    }
                    $alarm->name = $name;
                    $this->alarms->push($alarm);
                }
            } catch (AlhException $e) {
                print $e->getMessage();
                //TODO - we should throw to abort processing if we can't read ALH cleanly
            }
        }
    }

    protected function assertFileIsReadable($file)
    {
        if (!is_readable($file)) {
            throw new \Exception("Source file $file is not readable");
        }
    }

}
