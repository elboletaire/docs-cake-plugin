<?php

class DocsController extends DocsAppController
{
    public $uses = array();

    public $components = array(
        'Docs.Markdown'
    );

    /**
     * The main method. You will access
     * markdown files using this method.
     *
     * @param  string $path Not necessary as $path is taken from params
     * @return HttpResponse
     */
    public function view($path = null)
    {
        return $this->Markdown->render();
    }

    /**
     * Alias method for `view`.
     */
    public function index($path = null) { return $this->view(); }

    /**
     * Alias method for `view`.
     */
    public function admin_index($path = null) { return $this->view(); }
}
