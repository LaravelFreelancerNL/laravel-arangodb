<?php

namespace LaravelFreelancerNL\Aranguent;

/**
 * Class Document
 * Loosely based on ArangoDBClient\Document;
 * Laravel typecasts input data to an array which causes issues with protected variables (\0*\0 is prepended).
 * Thus we are using a very minimum version to gather data from ArangoDB through their PHP driver.
 */
class Document
{
    public function __construct(array $options = null)
    {
    }

    public static function createFromArray($values, array $options = [])
    {
        $document = new self($options);
        foreach ($values as $key => $value) {
            $document->set($key, $value);
        }

        return $document;
    }

    /**
     * Set a document attribute.
     *
     * The key (attribute name) must be a string.
     * This will validate the value of the attribute and might throw an
     * exception if the value is invalid.
     *
     *
     * @param string $key   - attribute name
     * @param mixed  $value - value for attribute
     *
     * @return void
     */
    public function set($key, $value)
    {
        $this->$key = $value;
    }
}
