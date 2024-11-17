<?php

namespace App\Model;

use App\Exceptions\AlhException;
use Illuminate\Support\Collection;

class AlhActionsList
{

    function __construct(protected Collection $actions = new Collection)
    {
    }

    protected function getActions(): Collection
    {
        if ($this->actions->isEmpty()) {
            foreach ($this->files() as $file) {
                $this->read($file);
            }
        }
        return $this->actions;
    }

    public function actions(): Collection
    {
        return $this->getActions();
    }


    public function distinctActionNames(){
        return $this->actions()->pluck('name')->unique()->sort();
    }

    /**
     * Get the list of source files that contain ALH definitions.
     *
     */
    function files(): array
    {
        $files = [];
        foreach (scandir($this->actionsDir()) as $item) {
            $path = $this->actionsDir() . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $this->assertFileIsReadable($path);
                $files[] = $path;
            }
        }
        return $files;
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
                    $action = json_decode($data, false);
                    if (!$action) {
                        throw new AlhException("failed to parse line $lineNum of $file \n");
                    }
                    $action->name = trim($name);
                    $this->actions->push($action);
                }
            } catch (AlhException $e) {
                print $e->getMessage();
                //TODO - we should throw to abort processing if we can't read ALH cleanly
            }
        }
    }

    protected function actionsDir(){
        return config('settings.alh_source_directory'). DIRECTORY_SEPARATOR .'actions';
    }

    protected function assertFileIsReadable($file)
    {
        if (!is_readable($file)) {
            throw new \Exception("Source file $file is not readable");
        }
    }

}
