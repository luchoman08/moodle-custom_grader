<?php
/**
 * Created by PhpStorm.
 * User: sistemasases
 * Date: 9/10/18
 * Time: 02:06 PM
 */

require_once (__DIR__.'/../ErrorFactory.php');
require_once (__DIR__.'/../CustomError.php');


class APIErrorFactory extends ErrorFactory
{
    const RESOURCE_NOT_FOUND = 1001;

    /**
     * Error for resource not found, resource can be an a resource given by a custom path, or
     * by a function requested via JSON.
     *
     *
     * @param stdClass|array $data Can have or not the property `resource`
     *  for display this in the message of the error
     *
     * @return Error
     */
    public static function resource_not_found($data = null): CustomError {
        $data = (object) $data;
        $message = "Recurso no encontrado";

        if(isset($data->resource)){
            $message .= ": $data->resource";
        }
        if(isset($data->method)){
            $message .= ":$data->method.";
        }
        if(isset($data->resources_available)){
            $message .= " Recursos disponibles: $data->resources_available.";
        }
        return new CustomError(
            APIErrorFactory::RESOURCE_NOT_FOUND,
            $message,
            $data);
    }


}