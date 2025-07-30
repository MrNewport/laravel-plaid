<?php

namespace MrNewport\LaravelPlaid\DTOs;

abstract class BaseDTO
{
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        $array = [];
        
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                if ($value instanceof BaseDTO) {
                    $array[$key] = $value->toArray();
                } elseif (is_array($value)) {
                    $array[$key] = array_map(function ($item) {
                        return $item instanceof BaseDTO ? $item->toArray() : $item;
                    }, $value);
                } else {
                    $array[$key] = $value;
                }
            }
        }
        
        return $array;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}