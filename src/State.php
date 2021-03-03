<?php


namespace Rudl\GitDb;


class State
{


    private $data = [];

    public function __construct(
        private string $fileName = "/tmp/gitdb-status.ser"
    )
    {
        if ( ! file_exists($this->fileName))
            file_put_contents($this->fileName, serialize([]));
        $this->data = unserialize(file_get_contents($this->fileName));
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
        file_put_contents($this->fileName, serialize($this->data));
    }
}