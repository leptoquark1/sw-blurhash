# Blurhash for Shopware <small>_- Teaser your Images_</small>

![Release](https://img.shields.io/github/v/release/leptoquark1/sw-blurhash?include_prereleases&style=flat-square)
![GitHub](https://img.shields.io/github/license/leptoquark1/sw-blurhash?style=flat-square)
![ShopwareVersion](https://img.shields.io/static/v1?label=Shopware&message=%5E6.4.0&color=189EFF&logo=shopware)

#### _Look how a **tiny**, **blurry** placeholder enhances the **user experience** by creating a **strong visual impact**_

---

### Not a _placeholder_, but a wholesome Teaser Image!

- 🏞 **Teaser Images** with a **blurry** representation of the original image
- 🔥 **Tiny!** Only a few dozen bytes <small>(~0.0001% of the image)</small>, therefore delivered with the **first response**
- 🚀 Decoded **by the clients'** browser **itself**
- 🌈 Configuration of **Performance** and **Quality** parameters to **fit your needs**
- 💻 **Emulated integration** - targets **all types of images** used by the storefront and your theme
- 😎 Quite casually: **Lazy images**, **reduced jumping of content** and much more...

---

> <small>A full integration of [Blurhash](https://blurha.sh/) for Shopware 6.</small>

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

### 4.1.0 UX & Controlling

- Exclude folders in media browser (like plugin config)
- Consider the HTML5 `img` 'lazy' attribute in combination with blurhash

### 4.2.0 Cover Traces (Garbage Collecting)

- List all missing images (those which do not meet the 'exclusion' configuration settings)
  - add a cli command with structural output; List all images with blurhash that no longer fit the 'exclusion' config
  - clean up existing command and (if possible) make it to be triggered from administration
- Remove a blurhash from image
  - Using the media browser:
    - single images
    - folders
  - Possibility to fully remove all generated Blurhashes when the plugin is uninstalled

### 4.3.0 Increase compatibility - Vue Storefront and Custom Integrations

- Api Integration of `MediaHashId` endpoint
  - read single blurhash for media entities and thumbnails
  - read lists of media entities (filtered, searchable)
  - write Blurhash for media entities (upsert backbone for outsourced generation)
  - validate a list of media entities for compatibility
  - list missing images (Those which not meet the 'exclusion' configuration settings)

### Backlog

- Emulated integration should be compatible with background images
- Support for images provided by CDNs
- Display a message now and then, when in manual mode

#### Conceptual

- Individual placeholders / loading state indicators while blurhash is processing
- File size limitation; Default exclusion for tiny and small images

##### Integration Tweaks

Better performance on slow / weak devices

- Compare lazy decoding of images (those not in view port) on pages with a lot of images:
  - Queue order should match the actual position of the Images in DOM
  - Fixed or absolut image position should be respected - can their position in queue be corrected?
- Hash Media-queries: Different hash sizes by device size (media-query), user-agent and similar classifications
- Blurhash decoding using WebAssembly (First try in Rust was significant slower than the current implementation)

## System Requirements

### Environment

__PHP__

- PHP version >=7.4.3 | >=8.x
- ext-gd (GD Graphics Library)
- ext-json

## Configuration

In order to adapt the plugin to your very special needs, you have a number of useful configuration parameters at your disposal.

> **All configurations will apply to all SalesChannels!**

### Decoding

#### Integration mode (Storefront)

This configuration is the essential part if you are using a Theme that make use of the [Shopware Storefront](https://github.com/shopware/storefront). If you use the [Shopware PWA](https://www.vuestorefront.io/shopware) or another frontend solution, you can safely ignore this configuration.

> <small>Are using a custom frontend solution? [Learn how to integrate Blurhash in your SPA](#custom-integrations)</small>

| Name | Description |
| ---- | ----------- |
| None | Want to do your very own thing? Fine! |
| Custom | For your own custom integrations, with this option only the basic functionality is provided need for decoding. |
| Emulated | Full integration providing the best possible coverage; [Learn more about](#emulated---event-lifecycle) |

##### Emulated - Event Lifecycle

1. While DOM is rendering
1. Detected all images that should be encoded
2. Images are distributed in two queues according to its relative emphasis: 'KeyVisual' & 'Subsequent'
3. The image `source-set` is 'lazy' and regular transparent placeholder is provided
4. For 'KeyVisuals', the `srcset` is provided to delegate browsers fetch of the correct image for it
2. After DOM is interactive
1. Blurhash decoding for 'KeyVisuals' are processed
2. Timeout any Blurhash decoding for `200ms` to have a reference distinction for Decoding vs. Final image response.
3. Is the final image not loaded yet, the placeholder itself gets replaced by the decoded Blurhash Teaser Image

[comment]: <> (TODO Visualize Lifecycle as a Diagram)

### Inclusions / Exclusion

Specify which images are either allowed or not allowed for processing.

| Configuration | Description | Default |
| ------------- | ----------- | ------- |
| Include protected Images | Protected images are those that are not accessible to everyone through a public URL | Yes |
| Excluded Folders | Images in these folders are ignored. | - |
| Excluded Tags | Images with these tags are ignored. | 'No Blurhash' |

### Performance

These configurations have a direct impact on performance. Keep this in mind when adjusting these values.

| Configuration | Type | Allowed values | Default | Description |
| ------------- | ---- | -------------- | ------- | ----------- |
| Max. Height (Thumbnail Threshold) | Integer | (unsigned) | 1000 | If this value is exceeded, a thumbnail is used instead of the original image |
| Max. Width (Thumbnail Threshold) | Integer | (unsigned) | 1000 | If this value is exceeded, a thumbnail is used instead of the original image |

### Encoding

#### Manual Mode

In manual mode, you have sole control of the images that are processed with Blurhash. This applies especially for automated operations such as the processing of new or modified images.

#### Components

You can adjust the level of detail of the blurred result.

To calculate the hash, components are extracted from the source image and represent something like a compressed colour spectrum of the original. You can specify the number of these extracted components vertically and horizontally.

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

If you want to use another Image Library than 'GD Graphics Library' than you are free to do so by decorating the `\Eyecook\Blurhash\Hash\Adapter\HashImageAdapterInterface`. Make sure your class actual implements this interface.

### Hash Generator

If you have the need to write your own 'Hash Generator' you can decorate the `\Eyecook\Blurhash\Hash\HashGeneratorInterface`. This can be useful if you want to delegate the generation to an external processor. You need to make sure your class actual implements the original interface.

### Custom Integrations

> Custom Integrations are currently not yet fully supported. This is why there is a lack of documentation.
> If you have problems setting up your own integration, you can create a message and describe the problem in detail.

#### JS Bundles

You are free to use existing chunk bundles for your storefront integration.

| Name | Path | Description | 
| ---- | ---- | ----------- |
| Decode | `bundles/ecblurhash/ecb-decode.js` | Straight basics to create a ready-to-use image resource from a blurhash added to the global window object |
| Helper | `bundles/ecblurhash/ecb-helper.js` | Some useful helper that might be needed added to window property `ecbHelper` |

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
