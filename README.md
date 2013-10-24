# BlackBox

There are lots of great PHP frameworks out at the moment, and they're all making strides towards interoperability. Whilst this is great for developers who have the expertise to pick and choose what they want in their framework, there is a growing need for a simpler solution.

Existing frameworks are very powerful, but they can also be overpowered, bloated and too complicated to understand. Often they work on the 'white box' principle, where the developer can see how everything works. But its getting to the point where developers are required to know every nut and bolt in their framework of choice in order to use it efficiently. This has the unfortunate side effect of raising the entry bar for new developers, which puts our industry at risk of stagnation.

As a result, we're developing the BlackBox Framework on the principle that a developer doesn't need to know how a framework works, they just need to know how to use it.

Our aim is to create a fully featured PHP framework which is:

- Fast, scalable and portable
- Easy to install and update
- Simple to use for novices
- Powerful enough for experts
- And inspires a new generation of web developers

## Download
BlackBox comes as a single PHP Archive (PHAR) file. The framework is still in its infancy, so we would strongly advise against using it for a real project. If you want to play around with it, you can [download the framework here](https://github.com/BlackBoxFramework/BlackBox/raw/master/blackbox.phar).

In order for BlackBox to run, you must use the following folder structure

	Root Directory
	|
	|-- Filter
	|
	|-- Model
	|
	|-- Public
	|   \_index.php
	|
	|-- Template
	|
	\_ blackbox.phar
	\_ config.json
	\_ routes.json

Your web server should load the index.php file in the `Public` directory. This should in turn have the following line of code:

	<?php require '../blackbox.phar';?>

## Methodology

BlackBox doesn't completely follow the traditional MVC framework, but it should be familiar to most. There are five basic components to an application that runs on BlackBox:

### Configuration
Configuring a framework can sometimes be quite a complex task, especially if there are lots of different components to customize. In BlackBox there is a single JSON config file which defines how BlackBox behaves. Full documentation will be written to explain what each option does.

### Routes
Like any other framework, BlackBox relies on predefined routes in order to figure out what to do. It can be quite tedious for a developer to write out a big list of routes in PHP, so in BlackBox we've opted for a single JSON file. Main features include:

- Nesting
- Parametric routes

Typically each route should define a `template`, and any applicable `models` and `filters`. Nesting is achieved by storing a route within the `children` node.

#### Example

	{
		"/" :
		{
			"template"	: "home"
		},

		"/blog" : 
		{
			"template" 	: "blog.list",
			"models"	: ["blog", "user"],

			"children" : {
				"/#year" :
				{
					"template" 	: "blog.post",
					"models"	: ["blog(year)"]

					"children"	: {
						"/:title" : {
							"template" 	: "blog.post",
							"models"	: ["blog(title)"]
						}
					}
				}
			}
		}
	}

### Filters
Filters are classes which are run before the request is fully completed. A good example of a filter would be something like a user authentication filter. Every filter must extend the base framework filter class.

*This isn't fully implemented yet, so watch this space!*

### Models
Models represent the data stored in your database. MVC frameworks normally use relational databases, but we'll be using non-relational databases instead. They're still new, but they're quicker to develop with and are easier to scale. Every model must extend the base framework model class.

*This isn't fully implemented yet, so watch this space!*

### Templates
Templates should be at the core of the development experience. The plan is to build a fully fledged templating engine which simplifies the whole process of creating a website.

*This isn't fully implemented yet, so watch this space!*

## The future of BlackBox
BlackBox has only just reached the proof of concept stage, so we're far from achieving our aims. In addition to the 'Web Service', we have plans to create a 'Api Service' which will seamlessly fit in with JavaScript frameworks and a 'CLI Service' for managing your application.

If you have any questions or feature requests, please feel free to raise an issue or get stuck in!