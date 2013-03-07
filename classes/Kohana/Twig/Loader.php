<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Loads template from the Kohana filesystem.
 */
class Kohana_Twig_Loader extends Twig_Loader_Filesystem {

    private $extension;

    public function __construct($extension) {
        $this->extension = $extension;
    }

    /**
     * Find the template using the find_file method.
     *
     * @param  string $name The name of the template
     * @return string The full path to the template.
     */
    protected function findTemplate($name) {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // File details
        $file = pathinfo($name);

        $extension = isset($file['extension']) ? $file['extension'] : $this->extension;

        // Full path to the file.
        $path = Kohana::find_file('views', $file['dirname'] . DIRECTORY_SEPARATOR . $file['filename'], $extension, FALSE);

        if (FALSE === $path) {
            throw new RuntimeException(sprintf('Unable to find template "%s".', $name));
        }

        return $this->cache[$name] = $path;
    }

}
