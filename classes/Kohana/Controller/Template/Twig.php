<?php

defined('SYSPATH') OR die('No direct script access.');

/**
 * Abstract controller class for automatic templating with Twig template engine.
 */
abstract class Kohana_Controller_Template_Twig extends Controller {

    /**
     * @var boolean Auto-render template after controller method returns
     */
    public $auto_render = TRUE;

    /**
     * @var Twig_Environment
     */
    protected $environment = 'default';

    /**
     * @var type Data context
     */
    private $context = array();

    /**
     * Assigns the template [View] as the request response.
     */
    public function after() {
        if ($this->auto_render === TRUE) {
            $this->response->body(
                Twig::factory(
                    $this->get_controller_path(). DIRECTORY_SEPARATOR. $this->request->action(),
                    $this->context,
                    $this->environment
                )->render()
            );
        }

        parent::after();
    }

    protected function assign($key, $value = NULL) {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                $this->context[$name] = $value;
            }
        } else {
            $this->context[$key] = $value;
        }
    }

    private function get_controller_path() {
        $directory = strlen($this->request->directory()) === 0 ? '' : join(DIRECTORY_SEPARATOR, array_map(function($path) {
            return lcfirst($path);
        }, explode('_', $this->request->directory()))) . DIRECTORY_SEPARATOR;

        return $directory . join(DIRECTORY_SEPARATOR, array_map(function($path) {
            return lcfirst($path);
        }, explode('_', $this->request->controller())));
    }

}