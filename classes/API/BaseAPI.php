<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 4/11/18
 * Time: 07:22 PM
 */
require_once (__DIR__ . '/../Errors/Factories/APIErrorFactory.php');
require_once (__DIR__ . '/../common/Validable.php');
require_once (__DIR__ . '/BaseAPIView.php');
require_once (__DIR__ . '/APIFunction.php');
/**
 * Class BaseAPI
 * Is validable because the function called
 */
class BaseAPI  extends Validable {
    /**
     * @var array $functions Array of APIFunction
     */
    private $functions;
    /**
     * @var stdClass $params The params send to php
     */
    private $params;

    public function __construct()
    {
        parent::__construct();
        $this->functions = array();
        $this->resources = array();
        return $this;
    }

    /**
     * Convert a view class to a function closure,
     * the view class should be a class than extends of BaseAPIView
     * @param $viewClass
     * @return Closure
     */
    private static function view_class_to_function($viewClass) {
        return function($args, $data) use ($viewClass) {
            $viewInstance = new $viewClass();
            return $viewInstance->execute($args, $data);
        };
    }
    /**
    * Add endpoint to API
    * $method string HTTP Method for the endpoint, can be GET, POST, PUT OR DELETE
    * $path_format_or_func_name string
    * $endpoint_element closure|string  If a closure is given, an instance of APIFunction is
    *   created in base to this closure, if string is given is spected than this is aclass name
    *   than extends of BaseAPIView
    */
    private function add_endpoint($path_format_or_func_name, $endpoint_element, $method='GET') {
        if(is_object($endpoint_element) && ($endpoint_element instanceof Closure)) {
            $this->add_function(new APIFunction($path_format_or_func_name, $endpoint_element, $method));
        } elseif (is_subclass_of($endpoint_element, BaseAPIView::class)) {

            $function = BaseAPI::view_class_to_function($endpoint_element);
            $this->add_function(new APIFunction($path_format_or_func_name, $function, $method));
        } else {
            throw(new Error('The end point element at get function in base api should be a closure or an instance of BaseAPIView'));
        }
    }
    
    /**
     * Add a element to endponint, this can be a closure with one argument (typically called $args)
     * where this argument is the url argument if exist
     * @param $path_format_or_func_name
     * @param $endpoint_element
     */
    function get($path_format_or_func_name, $endpoint_element) {
        add_endpoint($path_format_or_func_name, $endpoint_element, 'GET');
    }
    
    function delete($path_format_or_func_name, $endpoint_element) {
        add_endpoint($path_format_or_func_name, $endpoint_element, 'DELETE');
    }
    function post($path_format_or_func_name, $endpoint_element) {
        add_endpoint($path_format_or_func_name, $endpoint_element, 'post');
    }
    function put($path_format_or_func_name, $endpoint_element) {
         add_endpoint($path_format_or_func_name, $endpoint_element, 'PUT');
    }
    /**
     * @param $function APIFunction
     */
    private function add_function($function) {
        if($function->path_format[0]!='/') {
            $function->path_format = '/'.$function->path_format;
        }
        array_push($this->functions, $function);
        array_push($this->resources, $function->path_format);
    }
    private function send_errors() {
        http_response_code(422);
        header('Content-Type: application/json');

        echo json_encode($this->get_errors_object());
    }
    /**
     * Extract the params from  `php://input` of if its not found at this,
     * search at $_POST, and make the params available at $this->params
     * @return _params
     */
    private function init_params() {
        global $_POST;
        $this->params = (array)json_decode(file_get_contents('php://input'));
        if(!$this->params || !$this->params != '') {
            $this->params = (array)$_POST;
        }

    }
    private function find_function($method, $path) {
        if($path[0] != '/') {
            $path = '/'.$path;
        }
        /** @var $function APIFunction */
        foreach ($this->functions as &$function) {
            if($method == $function->http_method && preg_match($function->_native_pattern, $path)) {
                return $function;
            }
        }
        return false;
    }

    private function get_all_resources_printable() {
        $functions_string = array();
        /** @var APIFunction $function */
        foreach ($this->functions as $function) {
            array_push($functions_string, substr($function->path_format,1).':'.$function->http_method);
        }
        return implode(',', $functions_string);
    }
    function run() {
        global $_SERVER;
        $method = $_SERVER['REQUEST_METHOD'];

        $path_info = $_SERVER['PATH_INFO'];

        $this->init_params();
        $target = null;
        $api_view = null;
        $data = null;
        $args = array();
        if(isset($this->params->function)) {
            /* Infering the function from POST data */
            $target =  $this->params->function;
            $function = $this->find_function($method, $target);
            if(isset($this->params->params)) {
                $data = $this->params->params;
            }
        } else { 
            /* Infering the function from PATH_INFO */
            $target = $path_info;
            $function = $this->find_function($method, $target);
            $data = $this->params; /* The request body is assumed as a data */
        }

        if( $api_view ) {
            $args = route_get_params($api_view->path_format,  $path_info);
            $function->execute($args, $data);
        } else {

            $this->add_error(
                APIErrorFactory::resource_not_found(
                    array(
                        'resource'=>$target,
                        'method' => $method,
                        'resources_available'=> $this->get_all_resources_printable())));

            $this->send_errors();
        }

    }
}
