# ecBlurhash <small>_- Teaser your Images_</small>

#### _Look how a **tiny**, **blurry** placeholder enhances the **User Experience** by creating a **strong visual impact**_

---

### Not a _placeholder_, but a wholesome Teaser Image!

- 🏞 **Teaser Images** with a **blurry** representation of the original image
- 🔥 **Tiny!** Only a few dozen bytes <small>(~0.0001% of the image)</small>, therefore delivered with the **first response**
- 🚀 Decoded **by the clients'** browser **itself**
- 🌈 Configuration of **Performance** and **Quality** parameters to **fit your needs**
- 💻 **Emulated integration** - targets **all types of images** used by the storefront and your theme
- 😎 Quite casually: **Lazy images**, **reduced jumping of content** and much more...

---

><small>A full integration of [Blurhash](https://blurha.sh/) for Shopware 6.</small>

## Table of contents

- [About Blurhash](#about-blurhash)
- [Claim & Classification for Shopware 6](#claim-for-shopware-6)
- [Features & Roadmap](#roadmap)
- [System Requirements](#system-requirements)
    - [Environment](#environment)
- [Configuration](#configuration)
    - [Decoding](#decoding)
    - [Inclusions / Exclusion](#inclusions--exclusion)
    - [Performance](#performance)
    - [Encoding](#encoding)
- [Customization](#customization)
    - [Custom Integrations](#custom-integrations)
- [Commands (CLI)](#commands-cli)
- [Licence](#licence)

## About Blurhash

According to itself, it describes to be as follows
> Blurhash is a compact representation of a placeholder for an image.

However, this does not illustrate the enormous clout that this particular feature brings to bear!

[comment]: <> (**That dense, squiggly blob will provide your customers your Theme in all its splendour and glory to its full magnitude, right from the very first response of Shopware!**)


## Claim for Shopware 6

The target of `EcBlurhash` is, to integrate the Blurhash functionality and manage its context in the Shopware 6 ecosystem.

**Optimized Performance & Resources**
<br> Generate as efficiently as possible using PHP and Shopware peer dependencies

**Emulated Integration**
<br> Integration into the default Shopware "Theme System", providing the best possible coverage

**Client optimized decoding**
<br> Fast generation of the _Blurhash Teaser Image_ (decoded Blurhash) in the client browser

**Controlling**
<br> Comfortable control the processing and integration in the Shopware Administration

**Easy adaptable for Custom Integrations**
<br> Option to outsource the generation process to skirt 'PHP', 'Process' or even the 'System' bottlenecks


## Roadmap

### 4.0.0 Release Candidate

- display in media browser whether a blurhash is possible
  - for folder
  - for single image
- trigger generation from administration
  - for single image from media browser (force for existing)
  - for a complete folder from media browser (force or missing)
  - for all that may missing or all from media browser main folder
- add default tag for image exclusion (and exclude from generation)
  - make sure this will be removed when uninstalling the plugin
- remove svg files from validation
- fix image parsing and bluhash decoding for images that were loaded by xhr.
  for example search popover or offcanvas menu and cart
- test if picture elements for as expected

### 4.1.0 UX & Controlling

- Exclude folders in media browser (like plugin config)
- Check if a file size limit is possible
- The HTML `img` 'lazy' attribute in combination with blurhash

### 4.2.0 Cover Traces (Garbage Collecting)

- List all image missing images (which do not meet the 'exclusion' config)
  - display a message now and then, when in manual mode
  - add a command with structural output
  - clean up command and make it this can be triggered in administration
- remove a blurhash from image
  - for single image from media browser
  - the possibility to fully remove all blurhashes when plugin is uninstalled
  - Command to list all images with blurhash that no longer fit the 'exclusion' config
  
### 4.3.0 Increase compatibility - Vue Storefront and Custom Integrations

- Api Integration of `MediaHashId` endpoint
  - Read single media entities
  - Read lists of media entities (filtered, searchable)
  - Write Blurhash for media entities (Outsource of generation)
  - Validate a list of media entities for compatibility
  - List missing image (Not meet the 'exclusion' config)

### Backlog

- Emulated Integration should be compatible with background images
- Support for images provided by CDNs

#### Conceptual

- Custom placeholder while blurhash is decoded

##### Integration Tweaks

Better performance on slow / weak devices

- Compare lazy decoding of images (Those not in view port) on pages with a lot of images:
  - Queue order should match the actual position of the Images in DOM
  - Fixed or Absolut image position should be respected - can their position in queue be corrected?
- Can the actual HTML5 'Lazy Loading' Attribute intercepted when triggered?
- Hash Media-queries: Different hash sizes by Device, User-Agent and similar.
- Blurhash decoding using WebAssembly 


## System Requirements

### Environment

__PHP__

- ext-gd (GD Graphics Library)


## Configuration

In order to adapt the plugin to your very special needs, you have a number of useful configuration parameters at
 your disposal.

> **All configurations will apply to all SalesChannels!**

### Decoding

#### Integration mode (Storefront)

This configuration is the essential part for installations of the [Shopware Storefront](https://github.com/shopware/storefront) to render the frontend.
If you use the [Shopware PWA](https://www.vuestorefront.io/shopware) or another frontend solution, you can safely ignore this configuration.

> <small>Are using a custom frontend solution? [Learn how to integrate Blurhash in your SPA](#custom-integrations)</small>

#### Emulated - Event Lifecycle

1. While DOM is rendering
   1. Detected all images that should be encoded
   2. Images are distributed in two queues according to its relative emphasis: 'KeyVisual' & 'Subsequent' 
   3. The image `source-set` is 'lazy' and regular transparent placeholder is provided
   4. For 'KeyVisuals', the `srcset` is provided to delegate browsers fetch of the correct image for it
2. After DOM is interactive
   1. Blurhash decoding for 'KeyVisuals' are processed
   2. Timeout any Blurhash decoding for `200ms` to have a reference distinction for Decoding vs. Final image response.
   3. Is the final image not loaded yet, the placeholder itself gets replaced by the decoded Blurhash Teaser Image
3. Relevant images captured while the browser is rendering the DOM.
4. Once it's interactive, the decoding starts for all images in the current viewport. All others images are postponed to be processed when the DOM is ready, so it won't disturb the UX.
5. Images will have an invisible placeholder to fill out the space until the decoding is complete.
6. The image of the decoded Blurhash replace the invisible placeholder to teaser the final image.
7. Finally, when the actual source image has been loaded, it takes place of decoded teaser.

[comment]: <> (TODO Visualize Lifecycle as a Diagram)

### Inclusions / Exclusion

Specify which images are either allowed or not allowed for processing.

| Configuration | Description | Default |
| ------------- | ----------- | ------- |
| Include protected Images | Protected images are those that are not accessible to everyone through a public URL | Yes |
| Excluded Folders | Images in these folders are ignored. | - |
| Excluded Tags | Images with these tags are ignored. | - |

### Performance

These configurations have a direct impact on performance. Keep this in mind when adjusting these values.

| Configuration | Type | Allowed values | Default | Description |
| ------------- | ---- | -------------- | ------- | ----------- |
| Max. Height (Thumbnail Threshold) | Integer | (unsigned) | 1000 | If this value is exceeded, a thumbnail is used instead of the original image |
| Max. Width (Thumbnail Threshold) | Integer | (unsigned) | 1000 | If this value is exceeded, a thumbnail is used instead of the original image |

### Encoding

#### Manual Mode

In manual mode, you have sole control of the images that are processed with Blurhash.
This applies especially for automated operations such as the processing of new or modified images.

#### Components

You can adjust the level of detail of the blurred result.

To calculate the hash, components are extracted from the source image and represent something like a compressed colour
 spectrum of the original. You can specify the number of these extracted components vertically and horizontally.

| Configuration | Type | Allowed values | Default |
| ------------- | ---- | -------------- | ------- |
| X-Components | Integer | 1-9 (unsigned) | 4 |
| Y-Components | Integer | 1-9 (unsigned) | 3 |

Whereby the principle applies: A **higher** value results in a **finer** spectrum!

A Live-Demo can be found on [blurha.sh](https://blurha.sh)

> <small>The higher the spectrum is chosen, the longer the Blurhash and hence the time needed for processing (Encoding and Decoding)!
> This can have a negative impact on performance and (depending on the settings and number of images, also affect the size of the delivered page!
> Which in turn can mean a lower SEO score.... </small>


## Customization

### Image Adapter (Graphics Library)

If you want to use another Image Library than 'GD Graphics Library' than you are free to do so by decorating 
the `\EyeCook\BlurHash\Hash\Adapter\HashImageAdapterInterface`.
Make sure your class actual implements this interface.

### Hash Generator

If you have the need to write your own 'Hash Generator' you can decorate the `\EyeCook\BlurHash\Hash\HashGeneratorInterface`.
This can be useful if you want to delegate the generation to an external processor.
You need to make sure your class actual implements the original interface.

### Custom Integrations

> Custom Integrations are currently not yet fully supported. This is why there is no documentation.
> If you have problems setting up your own integration, you can create a message and describe the problem in detail.


## Commands (CLI)

### Generate 

Process or enqueue the generation of Blurhashes for either only missing or renew existing media entities.

Usage:
`ec:blurhash:generate [options] [<entities>...]`

| Argument / Option | Description | | 
| ----------------- | ------------- | ---
| entities | Restrict to specific models. (_Comma separated list of model names._) | optional
| -a, --all | Include images that already have a hash. | optional
| -s, --sync | Rather work now than by message worker. | optional
| -d, --dryRun | Skip processing, just show how many media entities are affected | optional

Example:

```bash
bin/console ec:blurhash:generate product --all --sync
```
<small>This will process all product images right away</small>

> <small>It can be possible that some images will be skipped when processing as defined in configuration
> or because they are may be invalid. The output can differ from actual processed images.
> </small>


## Licence

This project is licensed under the MIT License.
