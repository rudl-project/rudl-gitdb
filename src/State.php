<?php


namespace Rudl\GitDb;


class State
{


    private $data = [];

    /**
     * @var \Phore\FileSystem\PhoreFile
     */
    private $stateFile;

    public function __construct(
        private string $fileName = "/tmp/gitdb-status.ser"
    )
    {
        $this->stateFile = phore_file($this->fileName);

        if ( ! $this->stateFile->exists())
            $this->stateFile->set_serialized([]);
        $this->data = $this->stateFile->get_serialized();
    }

    public function &get(array $path = []) : array
    {
        $el =& $this->data;
        foreach ($path as $curPath) {
            if ( ! isset ($el[$curPath]))
                $el[$curPath] = [];
            $el =& $el[$curPath];
        }
        return $el;
    }

    public function set(array $path, $newData) : mixed
    {
        $el =& $this->data;
        foreach ($path as $curPath) {
            if ( ! isset ($el[$curPath]))
                $el[$curPath] = [];
            $el =& $el[$curPath];
        }
        $el = $newData;
        return $el;
    }


    public function __destruct()
    {
        $this->stateFile->set_serialized($this->data);
    }
}