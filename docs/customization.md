## Customization

### Image Adapter (Graphics Library)

If you want to use another Image Library than 'GD Graphics Library' than you are free to do so by decorating the `\Eyecook\Blurhash\Hash\Adapter\HashImageAdapterInterface`. Make sure your class actual implements this interface.

### Hash Generator

If you have the need to write your own 'Hash Generator' you can decorate the `\Eyecook\Blurhash\Hash\HashGeneratorInterface`. This can be useful if you want to delegate the generation to an external processor. You need to make sure your class actual implements the original interface.

### Custom Integrations

> Custom Integrations not yet fully supported. This is why there is a lack of documentation.
> If you have problems setting up your own integration, you can create an issue and describe the problem in detail.

#### JS Bundles

You are free to use the existing chunk bundles:

| Name   | Path                                     | Description                                      | 
|--------|------------------------------------------|--------------------------------------------------|
| Decode | `bundles/eyecookblurhash/ecb-decode.js`  | Decode hashes and create image resources from it |
| Helper | `bundles/eyecookblurhash/ecb-helper.js`  | Some useful helper that might be needed          |