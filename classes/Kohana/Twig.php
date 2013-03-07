<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Acts as an object wrapper for HTML pages with Twig template engine.
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 */
abstract class Kohana_Twig extends Kohana_View {

    /**
     *
     * @var type
     */
    static private $environments = array();

    /**
     * Returns a new Twig object. If you do not define the "file" parameter,
     * you must call [Twig::set_filename].
     *
     *     $view = Twig::factory($file);
     *
     * @param   string  $file           view filename
     * @param   array   $data           array of values
     * @param   string  $environment    view environment name
     * @return  Twig
     */
    static public function factory($file = NULL, $data = NULL, $environment = 'default') {
        return new Twig($file, $data, $environment);
    }

    /**
     * Sets a global variable, similar to [Twig::set], except that the
     * variable will be accessible to all views.
     *
     *     Twig::set_global($name, $value);
     *
     * @param   string  $key    variable name or an array of variables
     * @param   mixed   $value  value
     * @return  void
     */
    static public function set_global($key, $value = NULL) {
        if (is_array($key)) {
            foreach ($key as $key2 => $value) {
                Twig::$_global_data[$key2] = $value;
            }
        } else {
            Twig::$_global_data[$key] = $value;
        }
    }

    /**
     * Assigns a global variable by reference, similar to [View::bind], except
     * that the variable will be accessible to all views.
     *
     *     Twig::bind_global($key, $value);
     *
     * @param   string  $key    variable name
     * @param   mixed   $value  referenced variable
     * @return  void
     */
    public static function bind_global($key, & $value) {
        Twig::$_global_data[$key] = & $value;
    }

    /**
     * Returns the full path of the current template
     *
     * @return string
     */
    private static function path($path, $env = 'default') {
        if (pathinfo($path, PATHINFO_EXTENSION)) {
            return $path;
        }

        $config = Kohana::$config->load('twig')->get($env);

        if (isset($config['loader']['extension'])) {
            return "{$path}.{$config['loader']['extension']}";
        }

        return $path;
    }

    /**
     * Loads Twig_Environments based on the
     * configuration key they represent
     *
     * @param string $env
     * @return Twig_Environment
     */
    private static function environment($env = 'default') {
        if (isset(self::$environments[$env])) {
            return self::$environments[$env];
        }

        $config = Kohana::$config->load('twig')->get($env);

        // Create the the loader
        $loaderClass = $config['loader']['class'];
        $loader = new $loaderClass($config['loader']['extension']);

        // Set up the instance
        $twig = new Twig_Environment($loader, $config['environment']);

        // Load extensions
        foreach ($config['extensions'] as $extension) {
            $twig->addExtension(new $extension);
        }

        // Add the sandboxing extension.
        // The sandbox seems buggy
        // So this dummy condition is there to avoid the bug
        // The error thrown is "Twig_Sandbox_SecurityError [ 0 ]: Calling "__toString" method on a "Twig" object is not allowed."
        if (!empty($config['sandboxing']['tags'])
                && !empty($config['sandboxing']['filters'])
                && !empty($config['sandboxing']['methods'])
                && !empty($config['sandboxing']['properties'])
        ) {
            $policy = new Twig_Sandbox_SecurityPolicy
                            (
                            $config['sandboxing']['tags'],
                            $config['sandboxing']['filters'],
                            $config['sandboxing']['methods'],
                            $config['sandboxing']['properties']
            );

            $twig->addExtension(new Twig_Extension_Sandbox($policy, $config['sandboxing']['global']));
        }

        self::$environments[$env] = $twig;

        return $twig;
    }

    /**
     * @var string The environment
     */
    private $environment;

    /**
     * Constructor
     *
     * @param array $data
     * @author Jonathan Geiger
     */
    public function __construct($file = NULL, array $data = NULL, $environment = 'default') {
        parent::__construct($file, $data);

        $this->environment = $environment;
    }

    /**
     * Magic method. See get()
     *
     * @param	string	variable name
     * @return	mixed
     */
    public function & __get($key) {
        if (array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        } elseif (array_key_exists($key, Twig::$_global_data)) {
            return Twig::$_global_data[$key];
        } else {
            throw new Kohana_Exception('View variable is not set: :var', array(':var' => $key));
        }
    }

    /**
     * Magic method, determines if a variable is set and is not NULL.
     *
     * @param   string  variable name
     * @return  boolean
     */
    public function __isset($key) {
        return (isset($this->_data[$key]) OR isset(Twig::$_global_data[$key]));
    }

    /**
     * Magic method, unsets a given variable.
     *
     * @param   string  variable name
     * @return  void
     */
    public function __unset($key) {
        unset($this->_data[$key], Twig::$_global_data[$key]);
    }

    /**
     * Sets the view filename.
     *
     * @throws  View_Exception
     * @param   string  filename
     * @return  View
     */
    public function set_filename($file) {
        $this->_file = $file;

        return $this;
    }

    /**
     * Renders the view object to a string. Global and local data are merged
     * and extracted to create local variables within the view file.
     *
     * Note: Global variables with the same key name as local variables will be
     * overwritten by the local variable.
     *
     * @throws   View_Exception
     * @param    view filename
     * @return   string
     */
    public function render($file = NULL) {
        if ($file !== NULL) {
            $this->set_filename($file);
        }

        if (empty($this->_file)) {
            throw new Kohana_View_Exception('You must set the file to use within your view before rendering');
        }

        // Combine local and global data and capture the output
        return self::environment($this->environment)->loadTemplate(
            self::path($this->_file, $this->environment)
        )->render( $this->_data + Twig::$_global_data );
    }

}