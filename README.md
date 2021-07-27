# EyecookBlurhash for Shopware6

**A complete integration of [Blurhash](https://blurha.sh/) for Shopware 6.**

Come and see how a maybe simple, blurry placeholder can have a strong visual impact

--- 

## Table of contents

- [About Blurhash](#about-blurhash)
    - [Claim & Classification for Shopware 6](#claim-for-shopware-6)
- [Features & Roadmap](#features--roadmap)
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

**That dense, squiggly blob will provide your customers your Theme in all its splendour and glory to its full magnitude, right from the very first 
 response of Shopware!**

### Claim for Shopware 6

The target of `EcBlurhash` is, to integrate the Blurhash functionality into the Shopware 6 ecosystem. Highly adaptable and customisable.

## Features & Roadmap

### Current

- Generate Blurhashes for all or only specific images.
- Configure performance and quality parameters.
- Emulate a full integration which targets all types of image-integration in your storefront.
- Reduce jumping Layouts: Until the client has decoded the Blurhash, a placeholder in form of transparent images with the same dimensions will take place in the image frame.

### Upcoming

- Custom placeholder (not only a transparent images) and loading indicators for the blurhash itself.
- Full support for custom integrations.
- Hash Media-queries: Different hash sizes by Device, User-Agent and more.


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

This configuration is the essential part for installations the [Shopware Storefront](https://github.com/shopware/storefront) to render the frontend.
If you use the [Shopware PWA](https://www.vuestorefront.io/shopware) or another frontend solution, you can safely ignore this configuration.

> <small>Are using a custom frontend solution? [Learn how to integrate Blurhash in your SPA](#custom-integrations)</small>

#### Emulated

##### Event timeline
1. Relevant images captured while the browser is rendering the DOM. 
2. Once it's interactive, the decoding starts for all images in the current viewport. All others images are postponed to be processed when the DOM is ready, so it won't disturb the UX.
4. Images will have an invisible placeholder to fill out the space until the decoding is complete.
5. The image of the decoded Blurhash replace the invisible placeholder to teaser the final image.
6. Finally, when the actual source image has been loaded, it takes place of decoded teaser.

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
the `\Eyecook\Blurhash\Hash\Adapter\HashImageAdapterInterface`.
Make sure your class actual implements this interface.

### Hash Generator

If you have the need to write your own 'Hash Generator' you can decorate the `\Eyecook\Blurhash\Hash\HashGeneratorInterface`.
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

| Argument / Option | Description | |   |
| ----------------- | ------------- | - |
| entities | Restrict to specific models. (_Comma separated list of model names._) | optional |
| -a, --all | Include images that already have a hash. | optional |
| -s, --sync | Rather work now than by message worker. | optional |
| -d, --dryRun | Skip processing, just show how many media entities are affected | optional |

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
