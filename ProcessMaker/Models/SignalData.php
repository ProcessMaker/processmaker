<?php

namespace ProcessMaker\Models;

/**
 * Represents Signal Data
 */
class SignalData
{
    private $id;

    private $name;

    private $detail;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param  string  $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  string  $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDetail(): string
    {
        return $this->detail;
    }

    /**
     * @param  string  $detail
     */
    public function setDetail(string $detail): void
    {
        $this->detail = $detail;
    }

    public function __construct($id, $name, $detail = '')
    {
        $this->id = $id ?? '';
        $this->name = $name ?? '';
        $this->detail = $detail ?? '';
    }
}
