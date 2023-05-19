## Configuration

In order to adapt the plugin to your needs, you have a number of useful configuration parameters at your disposal.

**All configurations will apply to all SalesChannels!**

### Decoding

#### Integration mode (Storefront)

When you intend to create use a custom integration you can disable the default Storefront Integration (Emulated) here

> <small>Plans on using a custom frontend solution? [Learn how to integrate Blurhash in your SPA](customization.md#custom-integrations)</small>

| Name        | Description                                                                                |
|-------------|--------------------------------------------------------------------------------------------|
| `None`      | Want to do your very own thing? Fine!                                                      |
| `Custom`    | With this option only the basic functionality is provided which you may need for decoding. |
| `Emulated`  | Full integration providing the best possible coverage                                      |

!> If you do not intend to use a custom integration, leave this setting on **Emulated**. <br>Otherwise, some images may no longer be displayed in the storefront!

### Inclusions / Exclusion

Specify which images are not allowed for processing.

| Configuration            | Description                                                | Default       |
|--------------------------|------------------------------------------------------------|---------------|
| Include protected Images | Include "private" images                                   | No            |
| Excluded Folders         | Images in these folders are ignored. (Without sub-folders) | __None__      |
| Excluded Tags            | Images with these tags are ignored.                        | 'No Blurhash' |

### Performance

These configurations have a direct impact on performance. Keep this in mind when adjusting these values.

| Configuration                     | Type    | Allowed values | Default | Description                                                                            |
|-----------------------------------|---------|----------------|---------|----------------------------------------------------------------------------------------|
| Max. Width (Thumbnail Threshold)  | Integer | (unsigned)     | 1400    | If this value (pixels) is exceeded, a thumbnail is used instead of the original image  |
| Max. Height (Thumbnail Threshold) | Integer | (unsigned)     | 1080    | If this value (pixels) is exceeded, a thumbnail is used instead of the original image  |

### Encoding

#### Manual Mode

In manual mode, you have sole control of the images that are processed with a Blurhash. Integrated Workflows do respect this setting, so nothing is process without your consent.

#### Components

You can adjust the level of detail of the blurred result.

To calculate the hash, components are extracted from the source image which represent something like a compressed colour spectrum of the original. You can specify the number of these extracted components vertically and horizontally.

| Configuration  | Type    | Allowed values | Default |
|----------------|---------|----------------|---------|
| X-Components   | Integer | 1-9 (unsigned) | 5       |
| Y-Components   | Integer | 1-9 (unsigned) | 4       |

Whereby the principle applies: A **higher** value results in a **finer** spectrum!

A Live-Demo can be found on [blurha.sh](https://blurha.sh)

?> **Please consider**: The higher the spectrum is chosen, the longer the Blurhash and hence the time needed for processing (Encoding and Decoding)!