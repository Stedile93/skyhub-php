<?php

namespace SkyHub\Api;

/**
 * BSeller Platform | B2W - Companhia Digital
 *
 * Do not edit this file if you want to update this module for future new versions.
 *
 * @category  SkuHub
 * @package   SkuHub
 *
 * @copyright Copyright (c) 2018 B2W Digital - BSeller Platform. (http://www.bseller.com.br).
 *
 * @author    Tiago Sampaio <tiago.sampaio@e-smart.com.br>
 */
trait Helpers
{
    
    /**
     * @param string $value
     * @param string $char
     * @param float  $density
     *
     * @return string
     */
    protected function protectString($value, $char = '*', $density = 0.5)
    {
        $len            = strlen($value);
        $protectionSize = (int) ($len * (float) $density);
        
        $sidesAmount    = max((int) (($len-$protectionSize)/2), 0);
        
        $left   = substr($value, 0, $sidesAmount);
        $right  = substr($value, -$sidesAmount, $sidesAmount);
        $middle = str_repeat($char, $protectionSize);
        
        $value = implode([$left, $middle, $right]);
        
        return $value;
    }
    
    
    /**
     * @param array                   $data
     * @param string                  $index
     * @param mixed|array|bool|string $default
     *
     * @return mixed|array|bool|string
     */
    protected function arrayExtract(array $data, $index, $default = false)
    {
        if (!$this->arrayIsNotEmpty($data, $index)) {
            return $default;
        }
        
        return $data[$index];
    }
    
    
    /**
     * @param array  $data
     * @param string $index
     *
     * @return bool
     */
    protected function arrayIsNotEmpty(array $data, $index)
    {
        return (bool) ($this->arrayIndexExists($data, $index) && $data[$index]);
    }
    
    
    /**
     * @param array  $data
     * @param string $index
     *
     * @return bool
     */
    protected function arrayIndexExists(array $data, $index)
    {
        return (bool) isset($data[$index]);
    }
}
