# BlackBox PHP Framework

BlackBox is a single-file PHP framework which aims to be :

- Fast, scalable and portable.
- Easy to install, update and distribute.
- Simple to use for novices.
- Powerful and flexible enough for experts.

## Getting Started

BlackBox is distributed in a single PHP Archive (PHAR) file. The framework is still in its infancy, so it's not advisable to use it in commercial projects. 

To get started, download the [latest release](https://github.com/BlackBoxFramework/BlackBox/releases). The .zip file will contain all the files and folders that you need to get going.

### Configuration

All the configuration in BlackBox is done in the `config.json` file. The release version will have a very basic configuration file, but there are more parameters you can use :

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Default</th>
			<th>Description</th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td>debug</td>
			<td>true</td>
			<td>Defines whether BlackBox shows errors or not. Setting to `true` will show errors.</td>
		</tr>
		<tr>
			<td>cache</td>
			<td>true</td>
			<td>Defines whether BlackBox caches compiled templates. Currently always set to `true`</td>
		</tr>
		<tr>
			<td>api</td>
			<td>true</td>
			<td>Defines whether the BlackBox built in API is accessible.</td>
		</tr>
		<tr>
			<td>api_filters</td>
			<td>none</td>
			<td>An array of filters which should be run when the API is accessed. E.g. `['oauth']`.</td>
		</tr>
		<tr>
			<td>assets</td>
			<td>none</td>
			<td>An array of static assets which should be compiled. BlackBox will concatenate files with the same extension into `/Public/assets`. Use `{asset(ext)}` in your template to generate a URL for the asset.</td>
		</tr>
		<tr>
			<td>mongo_db</td>
			<td>none</td>
			<td>Defines which MongoDB database to use.</td>
		</tr>
		<tr>
			<td>mongo_user</td>
			<td>none</td>
			<td>Defines which MongoDB user to use.</td>
		</tr>
		<tr>
			<td>mongo_pwd</td>
			<td>none</td>
			<td>Sets the password for the MongoDB user. (Not required if MongoDB isn't using security).</td>
		</tr>
	</tbody>
</table>

Your configuration file should have a `default` setting and also domain specific settings. Domains will inherit the default settings and then override them with domain specific parameters if they're defined.

### Routing

All routes are defined in the `routes.json` file. Like the configuration file, a very basic version is included in the release. Routes can take the following parameters :

<table>
	<thead>
		<tr>
			<th>Parameter</th>
			<th>Description</th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td>template</td>
			<td>This sets which template should be used. If your templates are organised into folders, use a `.` to denote a child template. E.g. `blog.item`.</td>
		</tr>
		<tr>
			<td>methods</td>
			<td>An array of accepted HTTP methods. E.g. `["GET", "POST"]`. If undefined, defaults to a `GET` request.</td>
		</tr>
		<tr>
			<td>filters</td>
			<td>An array of filters to be executed before the page is shown.</td>
		</tr>
		<tr>
			<td>models</td>
			<td>An array of models to be fetched before the page is shown. Dynamic route parameters can be passed to the model like this : `blog(slug)` and methods can be accessed like this `blog.first`.</td>
		</tr>
		<tr>
			<td>children</td>
			<td>A nested route definition.</td>
		</tr>
	</tbody>
</table>

Nested routes can be used using the `children` parameter. The child route will build on top of the parent route, so there is no need to define the whole route.

### Dynamic / Parametric Routing

BlackBox supports both static and dynamic routes. An alphanumeric route part us defined with a colon (`:`), and a numeric route part is defined using a hash (`#`). For example, to define a blog route you might use :

`/blog/#year/#month/:title`

### Redirects

Similar to the configuration file and the routes file, all redirects are done via `redirect.json`. The release file has a basic example of a redirect from `/foo` to `/bar`.

### Templating

BlackBox comes with a fully fledged templating engine. The file extension for a template is `.tpl`, and all your templates should be saved in the `Template` folder. You can also download syntax highlighting for Sublime Text [here](https://github.com/BlackBoxFramework/BlackBoxSublimeText).

#### Variables
Printing a variable is simply done by using two curly brackets, e.g. `{{$variable}}` . BlackBox automatically escapes HTML special characters using `htmlspecialchars()`.

You can also define a variable in your template like this : `{define($variable, 'Variable string')}`.

#### If statements
If statements in BlackBox templating work in the same way as in PHP :

```
{if($variable > 5)}

	# Your code for true

{else}

	# Your code for false

{/if}
```

#### Loops
BlackBox also supports `foreach`, `while` and `for` loops :

```
{foreach($array as $value)}

	# Your code

{/foreach}
```

```
{while($variable < 5)}

	# Your code

{/while}
```

```
{for($i=0; $i < 10; $i++)}

	# Your code

{/for}
```

Foreach loops are automatically wrapped in an if statement, which means you can have a default action if the array is empty :

```
{foreach($array as $value)}

	# Your code

{else}

	# Show this if array is empty

{/foreach}
```

#### Switch Statements
Switch statements can also be used in BlackBox :

```
{switch($variable)}

	{case(true)}
	
		# Your code for true
	
	{/case}

	{default}

		# Your code for detaul

	{/default}

{/switch}
```

#### Template Inheritance

Like other popular templating engines, BlackBox also supports template inheritance. To extend an existing template, use the `extend` keyword :

`{extend(master)}`

The master template can specify where to show specific inherited sections, by using the `yield` keyword :

`{yield(header)}`

The child template uses the `section` keyword to define an output for a particular yield section :

```
{section(header)}

	# Your header code

{/section}
```

You can also include a specific template by using the `include` keyword :

`{include(breadcrumbs)}`

Each child template should only extend one master template, however they can provide multiple sections / yields.

### Models
MongoDB is the default database used by BlackBox. To create a model, extend the `Model` class and fill in the `$table` variable. A simple blog model might look like this :

```
namespace Model;

class Blog
	Extends \Model
{
	protected static $table = 'blog';
}

```

### Filters
Filters are classes which are run before a request is completed. Typically they would hook into framework or model events to modify input or outputs. Similar to models, a filter just needs to extend the `Filter` class :

```
namespace Filter;

class Auth
	Extends \Filter
{
	public function boot()
	{
		# Your authentication code
	}
}
```

# Full Documentation
Complete documentation on templating, models and filters is being written and will be released soon.

# Contribution & Help
If you have any problems using BlackBox, please use GitHub's issue tracker. If you would like to contribute to BlackBox, please feel free to create a pull request. We only ask that your code is well commented and documented.