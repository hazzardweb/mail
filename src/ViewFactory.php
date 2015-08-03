<?php

namespace Hazzard\Mail;

use Illuminate\Contracts\View\Factory;

class ViewFactory implements Factory
{
    /**
     * View storage path.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * The array of view data.
     *
     * @var array
     */
    protected $data;

    /**
     * The path to the view file.
     *
     * @var string
     */
    protected $path;

    /**
     * Set view storage path.
     *
     * @param string $path
     */
    public function setStoragePath($path)
    {
        $this->storagePath = $path;
    }

    /**
     * Get the evaluated view contents for the given view.
     *
     * @param  string $view
     * @param  array  $data
     * @param  array  $mergeData
     * @return $this
     */
    public function make($view, $data = [], $mergeData = [])
    {
        $view = str_replace('.', '/', $view);

        $this->data = $data;
        $this->path = $this->storagePath.'/'.$view.'.php';

        return $this;
    }

    /**
     * Get the string contents of the view.
     *
     * @return string
     */
    public function render()
    {
        ob_start();

        extract($this->data);

        require $this->path;

        $contents = ob_get_contents();

        if (ob_get_contents()) {
            ob_end_clean();
        }

        return $contents;
    }

    /**
     * @inheritDoc
     */
    public function exists($view)
    {
    }

    /**
     * @inheritDoc
     */
    public function file($path, $data = [], $mergeData = [])
    {
    }

    /**
     * @inheritDoc
     */
    public function share($key, $value = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function composer($views, $callback, $priority = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function creator($views, $callback)
    {
    }

    /**
     * @inheritDoc
     */
    public function addNamespace($namespace, $hints)
    {
    }
}
