<?php

namespace FileLocator;

/**
 * A helper to resolve web resources.
 *
 * If you have a link to a page, then this refers to either an
 * absolute or relative path. After the reference, a browser to
 * navigate further from the current situation or of the base path.
 * This class is recreated with such a behavior. Also paths leading
 * back to be considered.
 *
 * Example:
 *
 * $locator = new FileLocator();
 * $locator->setBasePath('http://example.com/');
 *
 * // The base path will not changed.
 * $locator->find('css/style.css');
 * $locator->find('css/fonts.css');
 *
 * // The locator becomes a new current path "css"
 * $locator->find('css/fonts.css', true);
 * $locator->find('../fonts/Helvetica.ttf');
 *
 * Thus one is able to pursue paths of css files for example.
 */
class FileLocator
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $currentPath;

    /**
     * @var array
     */
    protected $resourceTypes = array(
        'png', 'jpg', 'gif', 'css', 'ttf',
    );

    /**
     * Constructor.
     *
     * @param array $resourceTypes The possible resource types
     */
    public function __construct(array $resourceTypes = array())
    {
        $this->resourceTypes = array_replace($this->resourceTypes, $resourceTypes);
    }

    /**
     * Sets the base path and sets the 
     * current path to the same location at
     * the moment.
     *
     * @param string $basePath The base path
     */
    public function setBasePath($basePath)
    {
        $this->basePath    = $basePath;
        $this->currentPath = $basePath;
    }

    /**
     * Finds the exact resource path.
     *
     * @param string  $resource The resource path
     * @param boolean $plunge   Wheater the current path becomes the dirname of serached resource
     *
     * @return string
     */
    public function find($resource, $plunge = false)
    {
        if (!$this->isValidResourceFile($resource)) {
            throw new \InvalidArgumentException(
                sprintf('The resource file "%s" is not valid.', $resource)
            );
        }

        // if first char of resource is "/"
        // sets the current dir to base dir.
        if (0 === strpos($resource, '/')) {
            $base = $this->basePath;
        } else {
            $base = $this->currentPath;
        }

        if (0 === strpos($resource, 'http')) {
            $path = $resource;
        } else {
            $path = $this->mergePaths($base, $resource);
        }

        if ($plunge) {
            $this->currentPath = dirname($path);
        }

        return $path;
    }

    /**
     * Merges two paths.
     *
     * Example:
     *  a:      http://example.com
     *  b:      css/foo/../style.css
     *  result: http://example.com/css/style.css
     *
     * @param string $a The base path
     * @param string $b The path that appends on the base
     *
     * @return string
     */
    private function mergePaths($a, $b)
    {
        // Appends a trailingslash at the end
        // of the base path if it has none.
        if ('/' !== substr($a, -1)) {
            $a .= '/';
        }

        // If the appending path as a trailingslash
        // as first character remove it.
        if (0 === strpos($b, '/')) {
            $b = substr($b, 1);
        }

        $combined = $a . $b;

        // if the path navigates any directories
        // backwards resolves the right.
        if (false !== strpos($combined, '..')) {
            $combined = $this->resolveBackwardsPath($combined);
        }

        return $combined;
    }

    /**
     * Resolves a directory backwards.
     *
     * @param string $resource The resource path
     *
     * @return string
     */
    private function resolveBackwardsPath($resource)
    {
        return preg_replace('/\w+\/\.\.\//', '', $resource);
    }

    /**
     * Wheather the resource is valid and a file or not.
     *
     * @param string $resource The resource path
     *
     * @return boolean
     */
    private function isValidResourceFile($resource)
    {
        $extension = substr(strrchr($resource, '.'), 1);
        return in_array($extension, $this->resourceTypes);
    }
}
