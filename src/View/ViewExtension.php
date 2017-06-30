<?php namespace Enstart\View;

use Enstart\App;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use League\Plates\Extension\URI;

class ViewExtension implements ExtensionInterface
{
    /**
     * @var Engine
     */
    protected $engine;

    /**
     * @var RouterInterface
     */
    protected $app;

    /**
     * @param Enstart\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Register the extension
     *
     * @param  Engine $engine
     */
    public function register(Engine $engine)
    {
        $this->engine = $engine;

        $this->engine->loadExtension(
            new URI($this->app->router->getRequestPath())
        );

        $engine->registerFunction('asset', [$this, 'asset']);
        $engine->registerFunction('route', [$this, 'route']);
        $engine->registerFunction('excerpt', [$this, 'excerpt']);
        $engine->registerFunction('queryString', [$this, 'queryString']);
        $engine->registerFunction('csrfToken', [$this, 'csrfToken']);
        $engine->registerFunction('csrfField', [$this, 'csrfField']);
    }

    /**
     * Append file modification timestamp for an asset
     *
     * @param  string $file
     * @return string
     */
    public function asset($file)
    {
        if (!$this->app->path('public')) {
            return $file;
        }

        $file   = '/' . ltrim($file, '/');
        $public = rtrim($this->app->path('public'), '/');
        $full   = $public . $file;

        if ($this->app->config->get('debug') !== true) {
            $path     = pathinfo($full, PATHINFO_DIRNAME);
            $filename = pathinfo($full, PATHINFO_FILENAME);
            $ext      = pathinfo($full, PATHINFO_EXTENSION);
            $minfile  = $path . '/' . $filename . '.min.' . $ext;
            $file     = is_file($minfile) ? substr($minfile, strlen($public)) : $file;
            $full     = $public . $file;
        }

        if (!is_file($full)) {
            return $file;
        }

        return $file . '?' . filemtime($full);

    }

    /**
     * Resolve a route
     *
     * @param  string $name
     * @param  array  $args
     * @return string
     */
    public function route($name, array $args = [])
    {
        return $this->app->router->getRoute($name, $args);
    }

    /**
     * Get an excerpt of a text string
     *
     * @param  string  $text
     * @param  integer $maxLength
     * @param  string  $suffix
     * @return string
     */
    public function excerpt($text, $maxLength = 300, $suffix = '...')
    {
        // Check if the body has a user defined "<!--more-->"-tag
        $more = stripos($text, '<!--more-->');
        if ($more !== false) {
            // We found a tag, use that position for the excertip
            // instead of the default
            return strip_tags(substr($text, 0, $more));
        }

        $text = strip_tags($text);

        if (strlen($text) > $maxLength) {
            $text      = substr($text, 0, $maxLength - strlen($suffix));
            $lastSpace = strrpos($text, ' ');
            $text      = substr($text, 0, $lastSpace);
            $text      .= $suffix;
        }

        return $text;
    }


    /**
     * Get the current query string
     *
     * @param  array  $add
     * @param  array  $remove
     * @return string
     */
    public function queryString(array $add = [], array $remove = [])
    {
        $qs     = $this->app->request->server('QUERY_STRING');
        $values = [];

        if ($qs) {
            parse_str($qs, $values);
        }

        foreach ($remove as $rmKey) {
            if (array_key_exists($rmKey, $values)) {
                unset($values[$rmKey]);
            }
        }

        $values = array_replace($values, $add);

        return '?' . http_build_query($values);
    }


    /**
     * Get a csrf token
     *
     * @param  string $name
     * @return string
     */
    public function csrfToken($name = null)
    {
        return $this->app->csrf->getToken($name);
    }


    /**
     * Get a hidden input field with a csrf token
     *
     * @param  string $name
     * @return string
     */
    public function csrfField($name = null)
    {
        return $this->app->csrf->getTokenField($name);
    }
}
