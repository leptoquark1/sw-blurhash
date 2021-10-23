# Changelog

## [4.0.0-RC1](https://github.com/leptoquark1/sw-blurhash/compare/v4.0.0-RC0...v4.0.0-RC1) (2021-10-23)


### ⚠ BREAKING CHANGES

* **integration:** modularization and enhancement of storefront plugin integration and utils ([dcd3a1f](https://github.com/leptoquark1/sw-blurhash/commit/dcd3a1f092a436de79c667207b5b58444089b19a))

### Bug Fixes

* **command:** avoid hard exit in initialization due to testing concerns ([5004401](https://github.com/leptoquark1/sw-blurhash/commit/5004401d306f7bf1e88703e81c1fad9ff30c65d3))
* **command:** exit codes and its related assertions ([92affc7](https://github.com/leptoquark1/sw-blurhash/commit/92affc7b2cbd8038bac71a4bbda6b4787210ea95))

## 4.0.0-RC0 (2021-09-12)


### ⚠ BREAKING CHANGES

* **encoding:** introduce true color method in adapter interface.
* **test:** Namespace and Classnames has been changed.

- `EyeCook\BlurHash\Test\TestCaseBase\ConfigServiceTestBehaviour` is now  `EyeCook\BlurHash\Test\ConfigMockStub`
- `EyeCook\BlurHash\Test\TestCaseBase\HashMediaFixtures` is now  `EyeCook\BlurHash\Test\HashMediaFixtures`

### Features

* new tag that is excluded by default ([ce175c3](https://github.com/leptoquark1/sw-blurhash/commit/ce175c3a7bb7c3d3d0dd887e20c6782e6daaef1d))
* **admin:** validate and generate blurhashes from media browser ([ca3251e](https://github.com/leptoquark1/sw-blurhash/commit/ca3251e7cf554dc1bf52473f8da2a9286506414a))
* **config:** method to check the shopware environment config `enable_admin_worker` ([c118965](https://github.com/leptoquark1/sw-blurhash/commit/c11896517c02b748096a84813513373c24bfeaec))
* **framework:** maintainable plugin uninstall ([1fc70d3](https://github.com/leptoquark1/sw-blurhash/commit/1fc70d34a7c222dbe87fbafcd8615867586ad078))
* api endpoint for hash generation ([93b99f1](https://github.com/leptoquark1/sw-blurhash/commit/93b99f1e3292a9583c62d206f9d0aa5b3f896346))
* api endpoint for media and folder validation ([a567e89](https://github.com/leptoquark1/sw-blurhash/commit/a567e89827a0a878d390347241434e396914e754))
* manual-mode leverage handling ([bbfbe28](https://github.com/leptoquark1/sw-blurhash/commit/bbfbe28b85205aef13842f06869ef28f96daa4a1))
* switch to a faster blurhash implementation `fast-blurhash` ([fe8be8c](https://github.com/leptoquark1/sw-blurhash/commit/fe8be8c2eda00efbbbe12163290f88188bd45250))
* **mvp:** initial plugin minimal version prototype ([10f3e05](https://github.com/leptoquark1/sw-blurhash/commit/10f3e05602670f89aa111d9bdb399a34adfa629f))

### Bug Fixes

* **emulated:** twig runtime error when thumbnail attributes not in template scope ([be6de03](https://github.com/leptoquark1/sw-blurhash/commit/be6de03bcf964e7d560942fa1136bfee84f33f98))
* **encoding:** images of with palette color encoded incorrectly ([0fed180](https://github.com/leptoquark1/sw-blurhash/commit/0fed180d3676abb202c387b58bf817c8f1e0bc0e))
* **encoding:** move linear determination to adapter interface ([0d88cd8](https://github.com/leptoquark1/sw-blurhash/commit/0d88cd81bb436c9a62e8dbb2e593f5bc53ec9088))
* **integration:** emulated integration does further process DOM mutations when document `readystate` complete ([bf70960](https://github.com/leptoquark1/sw-blurhash/commit/bf7096000dfcd07389262790f44472a24f8ca1f1))
* images won't load when browser cache is activated ([5dc493c](https://github.com/leptoquark1/sw-blurhash/commit/5dc493c9678da8affd5b9d57620dd5ace61f3b8b))
* media validations that depend on associations fail ([ac8f92a](https://github.com/leptoquark1/sw-blurhash/commit/ac8f92a3fa6f2ae5108380e247a5c88ad429e2e3))
* **validation:** compatibility issues with vector images ([677a1b8](https://github.com/leptoquark1/sw-blurhash/commit/677a1b8968d7dd26991eb09d113be07d6d383869))
* administration media preview ([dd736f5](https://github.com/leptoquark1/sw-blurhash/commit/dd736f5ca392d661616a40de2e007d3b22af3c8a))
* messenger is not handling generate hash message ([ab81016](https://github.com/leptoquark1/sw-blurhash/commit/ab81016aac3cd46e3a6a515b70404caca7b38ace))
* multiple decoding of the same hash in emulated integration ([d41487d](https://github.com/leptoquark1/sw-blurhash/commit/d41487d7ff4e15a0f221f58ffaaa53b672ea415d))
* placeholder not replaced when multiple images with the same hash in emulated integration ([28587d4](https://github.com/leptoquark1/sw-blurhash/commit/28587d40c93d8b6276d74eba56e0c9e0e9b44653))


* **test:** move classes from `TestCaseBase` sub to root-folder ([5e1a960](https://github.com/leptoquark1/sw-blurhash/commit/5e1a960eceeb16283c0e53f7a2f9a91d2f7b751c))
