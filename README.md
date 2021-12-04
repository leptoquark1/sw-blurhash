<p align="center">
  <img alt="EyecookBlurhash" src="docs/_images/intro.png">
</p>

# <p align="center">Blurhash <small>for Shopware</small><br><small><small>Teaser your Images</small></small></p>

<p align="center">
  <img src="https://img.shields.io/github/v/release/leptoquark1/sw-blurhash?include_prereleases&style=flat-square" alt="Current Release">
  <img src="https://img.shields.io/badge/license-Apache%202-green" alt="License">
  <img src="https://img.shields.io/static/v1?label=Shopware&message=%5E6.4.0&color=189EFF&logo=shopware" alt="Shopware Version">
</p>

**A full integration of [Blurhash](https://blurha.sh/) for Shopware 6.**

---

### Not a _placeholder_, but a wholesome Teaser Image!

- 🏞 **Teaser Images** with a **blurry** representation of the original image
- 🔥 **Tiny!** Only a few dozen bytes <small>(~0.0001% of the image)</small>, therefore delivered with the **first response**
- 🚀 Decoded **by the clients'** browser **itself**
- 🌈 Configuration of **Performance** and **Quality** parameters to **fit your needs**
- 💻 **Emulated integration** - targets **all types of images** used by the storefront and your theme
- 😎 Quite casually: **Lazy images**, **reduced jumping of content** and much more...

---

## Table of contents

- [About Blurhash](#what-is-blurhash)
- [Documentation](https://leptoquark1.github.io/sw-blurhash)
  - [Basic Usage](https://leptoquark1.github.io/sw-blurhash/#/guide)
  - [Configuration](https://leptoquark1.github.io/sw-blurhash/#/configuration)
  - [Customization](https://leptoquark1.github.io/sw-blurhash/#/customization)
  - [Integrated Workflows](https://leptoquark1.github.io/sw-blurhash/#/integrated-workflows)
  - [Commands (cli)](https://leptoquark1.github.io/sw-blurhash/#/commands)
- [System Requirements](#system-requirements)
- [Licence](#licence)

---

## What is Blurhash

According to itself, it describes to be as follows

> Blurhash is a compact representation of a placeholder for an image.

However, this does not illustrate the enormous clout that this particular feature brings to bear!

## System Requirements

### PHP Extensions

- PHP version >=7.4.3 | >=8.x
- ext-gd (GD Graphics Library)
- ext-json

## Licence

This software is created by David Fecke (leptoquark1) and licensed under [Apache 2](https://github.com/leptoquark1/sw-blurhash/blob/main/LICENSE).

Initial concept and underlying structure is derived from an [algorithm](https://github.com/woltapp/blurhash/blob/master/Algorithm.md) original created by [Wolt Enterprises](https://github.com/woltapp/blurhash), licensed under [MIT License](https://github.com/woltapp/blurhash/blob/master/License.md).
