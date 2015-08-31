<?php
namespace YouHosting;

/**
 * Class AbstractDataContainer
 * @package YouHosting
 */
abstract class AbstractDataContainer
{
    /**
     * Map all values in the array to their related values in the child object
     *
     * @param array $array
     */
    public function __construct($array = array())
    {
        foreach($array as $key=>$value){
            if(property_exists(get_class($this), $key)){
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the values of this container as an array
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}