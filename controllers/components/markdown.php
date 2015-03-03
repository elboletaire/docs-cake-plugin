<?php
/**
 * This MarkdownComponent uses the Github Markdown API
 * to return the rendered Markdown in HTML format.
 *
 * @copyright Òscar Casajuana <elboletaire at underave dot net>
 * @link https://github.com/elboletaire/docs-cake-plugin
 * @license http://www.apache.org/licenses/LICENSE-2.0.html Apache 2.0
 */
class MarkdownComponent extends Object
{
/**
 * Mime types for markdown files
 * @var array
 */
    public static $mimes = array(
        'text/plain',
        'text/html',
        'text/x-markdown'
    );
/**
 * Controller instance
 * @var Controller
 */
    private $controller;
/**
 * The path given from imploding $controller->params['pass']
 * @var string
 */
    private $path;
/**
 * HttpSocket instance
 * @var HttpSocket
 */
    private $Http;
/**
 * The default settings
 * @var array
 */
    private $defaults = array(
        // the base path where markdown files are stored
        'base_path'          => DOCS_PATH,
        // enable cache? recommended
        'cache'              => 'default',
        // layout used for rendering
        'layout'             => '/layouts/github',
        // remove all that `user-content-` prefixes from the resulting HTML
        'remove_uc_prefixes' => true,
        // if set will be called after processing the markdown into HTML
        'on_post_process'    => false,
        // if set will be called before the markdown is converted into HTML
        'on_pre_process'     => false,
        // set it to the base url to be set to markdown relative links
        'replace_base_url'   => array(
            'controller' => 'docs',
            'action'     => 'index',
            'plugin'     => 'docs'
        )
    );

    private $settings = array();

    /**
     * {@inheritdoc}
     */
    public function initialize(&$controller, $settings = array())
    {
        $this->controller = $controller;
        $this->path = implode('/', $controller->params['pass']);

        $this->initSettings($settings);
    }

    /**
     * The main method. You will use this method for easily
     * rendering markdown files. Otherwise, you can take a
     * look into it to know how to implement it into your app
     *
     * @param  string $path Can be null, taking $path from params
     * @param  array $settings
     * @return HttpResponse
     */
    public function render($path = null, array $settings = array())
    {
        $this->initSettings($settings);

        if (empty($path)) {
            $path = $this->path;
        }

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

        $this->controller->set(compact('markdown'));

        return $this->controller->render($this->settings['layout']);
    }

    /**
     * Just merges the given settings with the defaults
     *
     * @param  array $settings
     * @return void
     */
    public function initSettings(array $settings)
    {
        $this->settings = array_merge($this->defaults, $settings);
    }

    /**
     * Returns a markdown rendered using the github api
     *
     * @param  string $markdown Markdown as plain text
     * @return string           Parsed (HTML) markdown
     */
    private function renderMarkdown($markdown)
    {
        if (empty($this->Http)) {
            App::import('Core', 'HttpSocket');
            $this->Http = new HttpSocket();
        }

        $markdown = $this->prepareMarkdown($markdown);
        $html = $this->Http->post('https://api.github.com/markdown',
            json_encode(array(
                'text' => $markdown,
                'mode' => 'markdown'
            ))
        );

        return $this->postProcessMarkdown($html);
    }

    /**
     * Reads a markdown file.
     * If file exists on cache and its modified time
     * is the same than the saved one, the cache file
     * is served. Otherwise it will be rendered and
     * saved into the cache.
     *
     * @param  string $markdown_path The path to the file (relative to DOCS_PATH) to
     *                               be read.
     * @throws Exception             If file is not a valid markdown (code 23) or if
     *                               file does not exist (code 404).
     * @return string                The parsed markdown.
     */
    public function readMarkdown($markdown_path)
    {
        if (empty($markdown_path)) {
            $markdown_path = 'readme.md';
        }

        $realpath = realpath($this->settings['base_path'] . $markdown_path);

        if (!$realpath) {
            throw new Exception("File does not exist", 404);
        }

        $filemtime = filemtime($realpath);
        $cache_key = $this->getCacheKeyForPath($markdown_path);
        $mime      = mime_content_type($realpath);

        if (!in_array($mime, self::$mimes)) {
            throw new Exception("This is not a markdown file!", 23);
        }

        if ($cache = $this->cacheRead($cache_key)) {
            if ($cache['time'] === $filemtime) {
                $html = $cache['body'];
            }
        }

        if (!$cache || empty($html)) {
            $markdown = file_get_contents($realpath);
            if (!$html = $this->renderMarkdown($markdown)) {
                $this->cakeError('error500');
            }
            $this->cacheWrite($markdown_path, $filemtime, $html);
        }

        return $html;
    }

    /**
     * Renders a media file (image, audio.. whatever)
     *
     * @param  string $path Path to the file
     * @return HttpResponse
     */
    public function renderMedia($path)
    {
        $this->controller->view = 'Media';
        $fileinfo = pathinfo($path);
        $params = array(
            'id'        => $fileinfo['basename'],
            'name'      => $fileinfo['filename'],
            'extension' => $fileinfo['extension'],
            'path'      => $this->settings['base_path'] . dirname($path) . DS
        );
        $this->controller->set($params);
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
     * Prepares the markdown file before it is rendered
     *
     * @param  string $markdown The markdown contents
     * @return string           Modified markdown contents
     */
    private function prepareMarkdown($markdown)
    {
        if ($this->settings['replace_base_url']) {
            $url = $this->settings['replace_base_url'];
            // Add a dash to properly get the url with params
            array_push($url, '-');
            // Get the url removing the trailing dash
            $base = rtrim(Router::url($url), '-');

            // Replace relative urls with our base url
            $markdown = preg_replace('/\[([^\]]+)\]\(((?![a-z]{2,9}:\/\/)[^(]+)\)/', "[$1]($base$2)", $markdown);
        }

        if (is_callable($this->settings['on_pre_process'])) {
            $markdown = $this->settings['on_pre_process']($markdown);
        }

        return $markdown;
    }

    /**
     * Does some actions after getting the html result
     * of a markdown
     *
     * @param  string $markdown
     * @return string
     */
    private function postProcessMarkdown($html)
    {
        if ($this->settings['remove_uc_prefixes']) {
            // remove `user-content-` prefixes
            $html = str_replace('user-content-', '', $html);
        }

        if (is_callable($this->settings['on_post_process'])) {
            $html = $this->settings['on_post_process']($html);
        }

        return $html;
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
        if (!$this->settings['cache']) {
            return false;
        }
        return Cache::write(
            $this->getCacheKeyForPath($path),
            serialize(compact('time', 'body')),
            $this->settings['cache']
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
        if (!$this->settings['cache'] || !$cached = Cache::read($key, $this->settings['cache'])) {
            return false;
        }

        return unserialize($cached);
    }
}
