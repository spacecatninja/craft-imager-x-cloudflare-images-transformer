# Cloudflare Images transformer for Imager X

A plugin for using [Cloudflare Images](https://developers.cloudflare.com/images/) [transformations](https://developers.cloudflare.com/images/transform-images/) 
as a transformer in Imager X. Also, an example of [how to make a custom transformer for Imager X](https://imager-x.spacecat.ninja/extending.html#transformers).

_Please note, this plugin does not enable you to store images on Cloudflare, it only enable you to optimize any publicly available image on the Internet._

## Requirements

This plugin requires Craft CMS 5.0.0 or later, [Imager X 5.0.0](https://github.com/spacecatninja/craft-imager-x/) or later,
and an account with [Cloudflare](https://cloudflare.com/).

## Setting up Cloudflare Images

To enable transformations in Cloudflare, go to Images > Transformations from you account home, and click "Enabled" on the zone
you wish to use. This domain goes into the required `zoneDomain` config setting, see below. 

By default, this enables you to transform any image stored inside this domain. If you want to transform images on other domains,
like a staging domain, a tunnel-domain to you local server, some third-party service (thumbs from YouTube, Vimeo, etc), or something else, 
you need to add these domains to the list of "Sources" in the details for the domain (click the domain in the list in "Transformations").

## Usage

Install and configure this transformer as described below. Then, in your [Imager X config](https://imager-x.spacecat.ninja/configuration.html), 
set the transformer to `cloudflareimages`, ie:

```
'transformer' => 'cloudflareimages',
``` 

Transforms are now by default transformed with Cloudflare Images, test your configuration with a 
simple transform like this:

```
{% set transform = craft.imagerx.transformImage(asset, { width: 600 }) %}
<img src="{{ transform.url }}" width="600">
<p>URL is: {{ transform.url }}</p>
``` 

If this doesn't work, make sure the assets you're trying to transform are publicly available, and that the zone includes the 
location of the files (see above).


### Cave-ats, shortcomings, and tips

This transformer only supports a subset of what Imager X can do when using the default `craft` transformer. The 
Cloudflare Images API is quite limited, but supports the most common use-cases. Please refer to the 
[Cloudflare Images documentation](https://developers.cloudflare.com/images/transform-images/transform-via-url/) for
a complete list of what's supported.

Crop modes, width, height, ratio, quality and format, is automatically converted from the standard Imager parameters, 
while the rest of the additional options can be passed directly to Cloudflare using the `transformerParams` transform parameter. Example:

```
{% set transforms = craft.imagerx.transformImage(asset, 
    [400, 1200, 2/1], 
    { transformerParams: { brightness: 1.2, sharpen: 1 } }
) %}
```
As with all external services for image transforms, figuring out a way to work with it in development can be tricky. I generally recommend 
having the images in a staging environment (or production when the site is live) where you add any test content that you need, sync databases, 
and point the asset URLs, and the cloudflare sources, to that when working locally. You can also use grok or similar to tunnel through to your 
local dev server, but it quickly gets a bit tiresome to keep updated. And you can of course use the default `craft` transformer when working 
locally, and `cloudflareimages` in production.

Note that you can easily combine local transforms with Cloudflare transforms. Since Cloudflare doesn't really care where on the zone domain 
something is stored, you can do one transform with the local `craft` transformer, and pass the result to Cloudflare to transform further. This 
can be useful if you want to use some functionality that is not supported by Cloudflare, but is in Imager. Example:

```
{% mySpecialTransform = craft.imagerx.transformImage({ effects: { modulate: [90,50,100] } }, {}, { transformer: 'craft' }) %}
{% transforms = craft.imagerx.transformImage(mySpecialTransform, [600, 1800, 16/9]) %}
```

This of course requires that Cloudflare can access the image generated in the first step, so in this case, if you try it on a local dev
server, you need to figure out how to enable that. 


## Installation

To install the plugin, follow these instructions:

1. Install with composer via `composer require spacecatninja/imager-x-cloudflare-images-transformer` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings > Plugins, or from the command line via `craft plugin/install imager-x-cloudflare-images-transformer`.


## Configuration

You can configure the transformer by creating a file in your config folder called
`imager-x-cloudflare-images-transformer.php`, and override settings as needed.

### zoneDomain [string]
Default: `''`  
This is your cloudflare zone that has transformations enabled, and it's required for the plugin to work.

#### defaultParams [array]
Default: `[]`       
Default params to be passed to every transform. This is very handy for a couple of things, like:

```
'defaultParams' => [
    'format' => 'auto',
    'quality' => 80
 ]
```

_At least `'format' => 'auto'` should be the default in most cases. Then Cloudflare will determine what image formats the browser supports, and deliver the best
one._ 

It's also tempting to set `'gravity' => 'auto'` here, but see below.

### autoGravityWhenNoFocalPoint [bool]
Default: `false`  
This setting automatically applies `'gravity' => 'auto'` to transformations when an asset lacks a defined focal point. Unlike adding this parameter directly to `defaultParams` or in your transform configuration, enabling this setting preserves Craft's built-in focal point functionality. This gives you dual benefits:

1. Automatic focal point detection for assets without manually defined focal points
2. The ability to override automatic detection by setting focal points in Craft when needed

This approach provides flexibility while maintaining control over image transformations, especially useful when automatic detection doesn't produce optimal results.



Price, license and support
---
The plugin is released under the MIT license. It requires Imager X, which is a commercial 
plugin [available in the Craft plugin store](https://plugins.craftcms.com/imager-x). If you 
need help, or found a bug, please post an issue in this repo, or in Imager X' repo (preferably). 
