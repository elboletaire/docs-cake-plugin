# Cake 1.3 Docs plugin

This CakePHP plugin basically renders markdown files using the github api.

Use it to show help pages or to easily document your projects and make the info
available to the final client directly from the app.

And everything only writing markdown files!

## Installation

As a git submodule:

```bash
git submodule add https://github.com/elboletaire/docs-cake-plugin.git app/plugins/docs
```

## Kick-starting the plugin

You only need to define the docs path constant (`DOCS_PATH`) in order to use
this plugin.

To do so, on your `boostrap.php` file put this line:

```php
define('DOCS_PATH', '/path/to/your/markdown/files');
```

## Usage

By default this plugin searches for a `readme.md` file. So, if your folder does
not have that file, it will return an `error404`.

As this file does not have any route defined you'll need to access them using
the default cakePHP fallbacks:

- `/docs` should render `DOCS_PATH/readme.md`.
- `/admin/docs` should render `DOCS_PATH/readme.md`.
- `/docs/docs/view/anyother.md` should render `DOCS_PATH/anyother.md`.
- `/docs/docs/view/path/to/md/woha.md` should render `DOCS_PATH/path/to/md/woha.md`.

You can access directly `/docs` because the `view()` method has two aliases:
`index()` and `admin_index()`.

BTW, I recommend you creating your custom route:

```php
Router::connect('/admin/docs/*', array(
	'controller' => 'docs',
	'action'     => 'index',
	'plugin'     => 'docs',
	'admin'      => true
));
```

### Custom usage

If you would like just to render markdown files, you can do so using the
MarkdownComponent given with this plugin.

```php
// Your Controller or Component
public $components = array(
	'Docs.Markdown'
);
```

It has many options making it very flexible:

```php
public $components = array(
	'Docs.Markdown' => array(
        'base_path'          => DOCS_PATH,
        'cache'              => 'default',
        'layout'             => '/layouts/github',
        'remove_uc_prefixes' => true,
        'on_post_process'    => false,
        'on_pre_process'     => false,
        'replace_base_url'   => array(
            'controller' => 'docs',
            'action'     => 'index',
            'plugin'     => 'docs'
        )
	)
);
```

- `base_path`: By defaults points to `DOCS_PATH`. It should be an absolute path,
  but maybe you can try with relative paths too (relative to `WWW_ROOT`,
  obviously).
- `cache`: whether or not to enable the cache. To enable it specify a cache
  configuration key (by default enabled using default cache).
- `layout`: the layout to use when rendering markdowns using the `render()`
  method.
- `remove_uc_prefixes`: remove `user-content-` prefixes from resulting HTML.
- `on_pre_process`: callback used before processing the markdown file. It takes
  one argument: `$markdown` and your callback must return it (modified).
- `on_post_process`: same as `on_pre_process` but called after the markdown file
  has been rendered into a HTML file. Here is where `remove_uc_prefixes` take
  effect.
- `replace_base_url`: the url path to be prefixed to relative links. Set it to
  false to not replace relative links.

An example usage could be:

```php
public $components = array(
	'Docs.Markdown' => array(
		// disable some default settings so we get the original github version
		'replace_base_url'   => false,
		'remove_uc_prefixes' => false
	)
);

public function admin_add()
{
	if (!empty($this->data)) {
		$this->MyModel->set($this->data);
		if ($this->MyModel->validates()) {
			// Render a markdown to store it into the DB
			$this->data['MyModel']['parsed'] = $this->Markdown->renderMarkdown($this->data['MyModel']['markdown']);

			if ($this->MyModel->save($this->data)) {
				// Whatever
			}
		}
	}
}
```

## TODO

- Version for CakePHP 2.X/3.X
- Add non-API renderer (and set it as option)
- More templates

## License

Copyright 2014 Òscar Casajuana (a.k.a. elboletaire)

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
imitations under the License.
