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
