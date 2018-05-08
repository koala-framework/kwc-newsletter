<?php
namespace KwcNewsletter\Bundle\Importer\Parser;

use SplFileObject;
use Iterator;

class Csv implements Iterator
{
    /**
     * @var SplFileObject
     */
    private $file;

    /**
     * @var bool
     */
    private $hasHeader = true;
    /**
     * @var string
     */
    private $delimiter = ";";
    /**
     * @var string
     */
    private $enclosure = "\"";
    /**
     * @var string
     */
    private $escape = "\\";

    /**
     * @var array
     */
    private $header = null;
    /**
     * @var int
     */
    private $count = null;
    /**
     * @var array
     */
    private $data;

    public function setFile($file)
    {
        $this->file = new SplFileObject($file);
        $this->file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY | SplFileObject::READ_CSV);
        $this->file->setCsvControl($this->delimiter, $this->enclosure, $this->escape);
    }

    public function getHeader()
    {
        if ($this->header === null) {
            $currentPosition = $this->file->key();

            $this->file->seek(0);
            $this->header = $this->file->current();

            $this->file->seek($currentPosition);
        }

        return $this->header;
    }

    public function count()
    {
        if ($this->count === null) {
            $currentPosition = $this->file->key();

            $this->file->seek($this->file->getSize());
            $this->count = $this->file->key();
            if ($this->count > 0 && $this->hasHeader) $this->count--;

            $this->file->seek($currentPosition);
        }

        return $this->count;
    }

    public function current()
    {
        $this->buildData();
        return $this->data;
    }

    public function next()
    {
        $this->file->next();
        $this->buildData();
        return $this->data;
    }

    public function key()
    {
        return $this->file->key();
    }

    public function valid()
    {
        return $this->file->valid();
    }

    public function rewind()
    {
        $this->file->rewind();
        $this->buildData();
    }

    private function buildData()
    {
        if (!$this->valid()) return;

        if ($this->hasHeader && $this->key() === 0) {
            $this->file->next();
        }

        $line = $this->file->current();

        $this->data = array();
        foreach ($this->getHeader() as $column => $name) {
            $this->data[strtolower(trim($name))] = $line[$column];
        }
    }

    public function setHasHeader($hasHeader)
    {
        $this->hasHeader = $hasHeader;

        return $this;
    }

    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    public function setEscape($escape)
    {
        $this->escape = $escape;

        return $this;
    }
}
