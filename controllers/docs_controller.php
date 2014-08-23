<?php

class DocsController extends DocsAppController
{
    public $uses = array();

    private $Http;

    /**
     * The main method. You will access
     * markdown files using this method.
     *
     * @param  string $path Can be null as $path is taken from params
     * @return HttpResponse
     */
    public function view($path = null)
    {
        $path = implode('/', $this->params['pass']);

        try {
            $markdown = $this->readMarkdown($path);
        } catch (Exception $e) {
            switch ($e->getCode()) {
                case 404:
                    $this->cakeError('error404');
                case 23:
                    return $this->renderMedia($path);
            }
        }

        $this->set(compact('markdown'));

        return $this->render('/layouts/github');
    }

    /**
     * Alias method for `view`.
     */
    public function index($path = null) { return $this->view(); }

    /**
     * Alias method for `view`.
     */
    public function admin_index($path = null) { return $this->view(); }

    /**
     * Returns a markdown rendered using the github api
     *
     * @param  string $path Markdown path
     * @return string       Parsed markdown
     */
    private function renderMarkdown($path)
    {
        if (empty($this->Http)) {
            App::import('Core', 'HttpSocket');
            $this->Http = new HttpSocket();
        }

        $markdown = $this->prepareMarkdown(file_get_contents($path));
        return $this->Http->post('https://api.github.com/markdown',
            json_encode(array(
                'text' => $markdown,
                'mode' => 'markdown'
            ))
        );
    }

    /**
     * Writes a serialized markdown file into cache
     *
     * @param  string $path Markdown path
     * @param  int    $time filemtime value
     * @param  string $body Contents to be written to the cache
     * @return boolean
     */
    private function cacheWrite($path, $time, $body)
    {
        return Cache::write(
            $this->getCacheKeyForPath($path),
            serialize(compact('time', 'body'))
        );
    }

    /**
     * Reads markdown from cache
     *
     * @param  string $key Cache key to be read
     * @return string      Unserialized cache
     */
    private function cacheRead($key)
    {
        if (!$cached = Cache::read($key)) {
            return false;
        }

        return unserialize($cached);
    }

    /**
     * Prepares the markdown file before it is rendered
     *
     * @param  string $markdown The markdown contents
     * @return string           Modified markdown contents
     */
    private function prepareMarkdown($markdown)
    {
        $base = rtrim(Router::url(array(
            'controller' => 'docs',
            'action'     => 'index',
            'plugin'     => 'docs',
            '-'
        )), '-');

        // Replace urls with our base url
        $markdown = preg_replace('/\[([^\]]+)\]\(([^(]+)\)/', "[$1]($base$2)", $markdown);

        return $markdown;
    }

    /**
     * Reads a markdown file.
     * If file exists on cache and its modified time
     * is the same than the saved one, the cache file
     * is served. Otherwise it will be rendered and
     * saved into the cache.
     *
     * @param  string $path The file (relative to DOCS_PATH) to be read
     * @return string       The parsed markdown
     */
    private function readMarkdown($path)
    {
        if (empty($path)) {
            $path = 'readme.md';
        }

        $realpath = realpath(DOCS_PATH . $path);

        if (!$realpath) {
            throw new Exception("File dows not exist", 404);
        }

        $filemtime = filemtime($realpath);
        $cache_key = $this->getCacheKeyForPath($path);
        $mime      = mime_content_type($realpath);

        if (!in_array($mime, array('text/plain', 'text/x-markdown'))) {
            throw new Exception("This is not a markdown file!", 23);
        }

        if ($cache = $this->cacheRead($cache_key)) {
            if ($cache['time'] === $filemtime) {
                $markdown = $cache['body'];
            }
        }

        if (!$cache || empty($markdown)) {
            if (!$markdown = $this->renderMarkdown($realpath)) {
                $this->cakeError('error500');
            }
            $markdown = $this->postProcessMarkdown($markdown);
            $this->cacheWrite($path, $filemtime, $markdown);
        }

        return $markdown;
    }

    /**
     * Generates a cache key from given path
     *
     * @param  string $path
     * @return string       Of type `docs_prefix_path_to_file_md`
     */
    private function getCacheKeyForPath($path)
    {
        $prefix = 'docs_';
        if (isset($this->params['prefix'])) {
            $prefix .= $this->params['prefix'] . '_';
        }
        return $prefix . str_replace('/', '_', $path);
    }

    /**
     * Renders a media file (image, audio.. whatever)
     *
     * @param  string $path Path to the file
     * @return HttpResponse
     */
    private function renderMedia($path)
    {
        $this->view = 'Media';
        $fileinfo = pathinfo($path);
        $params = array(
            'id'        => $fileinfo['basename'],
            'name'      => $fileinfo['filename'],
            'extension' => $fileinfo['extension'],
            'path'      => DOCS_PATH . dirname($path) . DS
        );
        $this->set($params);
        return $this->render();
    }

    /**
     * Does some actions after getting the html result
     * of a markdown
     *
     * @param  string $markdown
     * @return string
     */
    private function postProcessMarkdown($markdown)
    {
        // remove `user-content-` from anchor links
        $markdown = str_replace('user-content-', '', $markdown);

        return $markdown;
    }
}
