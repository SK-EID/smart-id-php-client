<?php
/*-
 * #%L
 * Smart ID sample PHP client
 * %%
 * Copyright (C) 2018 - 2019 SK ID Solutions AS
 * %%
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * #L%
 */
namespace Sk\SmartId\Api\Data;

use Exception;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

abstract class PropertyMapper
{
  /**
   * @param array $data
   */
  public function __construct(array $data = array() )
  {
    if ( !empty( $data ) )
    {
      $this->fromArray( $data );
    }
  }

  /**
   * @param string $key
   * @param array $arguments
   * @return mixed
   * @throws Exception
   */
  public function __call(string $key, array $arguments )
  {
    if ( method_exists( $this, $key ) )
    {
      return call_user_func_array( array( $this, $key ), $arguments );
    }

    if ( property_exists( $this, $key ) )
    {
      if ( 0 == count( $arguments ) )
      {
        return $this->__get( $key );
      }
      elseif ( 1 == count( $arguments ) )
      {
        return $this->__set( $key, $arguments[0] );
      }
    }

    if ( 'set' == $key && 2 == count( $arguments ) )
    {
      return $this->__set( $arguments[0], $arguments[1] );
    }

    if ( 'get' == $key && 1 == count( $arguments ) )
    {
      return $this->__get( $arguments[0] );
    }

    if ( 'set' == substr( $key, 0, 3 ) && 1 == count( $arguments ) )
    {
      return $this->__set( lcfirst( substr( $key, 3 ) ), $arguments[0] );
    }

    if ( 'get' == substr( $key, 0, 3 ) && 0 == count( $arguments ) )
    {
      return $this->__get( lcfirst( substr( $key, 3 ) ) );
    }

    throw new Exception( 'Undefined method ' . $key . "!" );
  }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     * @throws ReflectionException
     */
  public function __set(string $key, $value )
  {
    $alternativeKey = ucwords( $key, '_' );
    $alternativeKey = str_replace( '_', '', $alternativeKey );
    $alternativeKey = lcfirst( $alternativeKey );

    if ( property_exists( $this, $key ) || property_exists( $this, $alternativeKey ) )
    {
      $resultingKey = property_exists( $this, $key ) ? $key : $alternativeKey;
      $camelCasedName = 'set' . $this->toCamelCase( $resultingKey );

      if ( method_exists( $this, $camelCasedName ) )
      {
        $result = $this->prepareValue( $camelCasedName, $value );
        $this->{$camelCasedName}( $result );
      }
      else
      {
        $this->{$resultingKey} = $value;
      }
    }

    return $this;
  }

    /**
     * @param string $method
     * @param array|mixed $value
     * @return mixed
     * @throws ReflectionException
     */
  private function prepareValue(string $method, $value )
  {
    if ( is_array( $value ) )
    {
      $Method = new ReflectionMethod( $this, $method );
      if ( $Method->getParameters()[0]->getClass() === null )
      {
        return $value;
      }
      $class = $Method->getParameters()[0]->getClass()->getName();
      $result = new $class( $value );
    }
    else
    {
      $result = $value;
    }
    return $result;
  }

  /**
   * @param string $key
   * @return mixed
   * @throws Exception
   */
  public function __get( string $key )
  {
    if ( property_exists( $this, $key ) )
    {
      $camelizedName = 'get' . $this->toCamelCase( $key );

      if ( method_exists( $this, $camelizedName ) )
      {
        return $this->{$camelizedName}();
      }
      else
      {
        return $this->{$key};
      }
    }
    else
    {
      throw new Exception( 'Undefined property (' . $key . ')!' );
    }
  }

    /**
     * @return array
     * @throws ReflectionException
     */
  public function toArray(): array
  {
    $values = get_object_vars( $this );

    foreach ( $values as $key => $value )
    {
      $Property = new ReflectionProperty( $this, $key );

      if ( $Property->isProtected() )
      {
        unset( $values[ $key ] );
      }
    }

    return $values;
  }

  /**
   * @param array $array
   * @return $this
   */
  public function fromArray( array $array )
  {
    foreach ( $array as $key => $value )
    {
      $this->{$key} = $value;
    }

    return $this;
  }

  /**
   * @param string|array $word
   * @return string|array
   */
  private function toCamelCase($word )
  {
    return preg_replace_callback( '#(^|_)([a-z])#', function( array $matches )
    {
      return strtoupper( $matches[2] );
    }, $word );
  }
}
