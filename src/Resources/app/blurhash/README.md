# Blurhash Integration for Shopware 6

## Emulated

### Event Lifecycle

#### While DOM is rendering

1. Detected all images that should be encoded
2. Images are distributed in two queues according to its relative emphasis: 'KeyVisual' & 'Subsequent'
3. The image `source-set` is 'lazy' and regular transparent placeholder is provided
4. For 'KeyVisuals', the `srcset` is provided to delegate browsers fetch of the correct image for it
5. After DOM is interactive
6. Blurhash decoding for 'KeyVisuals' are processed
7. Timeout any Blurhash decoding for `200ms` to have a reference distinction for Decoding vs. Final image response.
8. Is the final image not loaded yet, the placeholder itself gets replaced by the decoded Blurhash Teaser Image
