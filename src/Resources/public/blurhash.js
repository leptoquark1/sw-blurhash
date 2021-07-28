// noinspection ES6ConvertVarToLetConst
<!-- prettier-ignore-start -->
window.Blurhash = {
  _internal: {
    utils: {
      sRGBToLinear: function (value) {
        var v = value / 255;
        if (v <= 0.04045) {
          return v / 12.92;
        } else {
          return Math.pow((v + 0.055) / 1.055, 2.4);
        }
      },
      linearTosRGB: function (value) {
        var v = Math.max(0, Math.min(1, value));
        if (v <= 0.0031308) {
          return Math.round(v * 12.92 * 255 + 0.5);
        } else {
          return Math.round((1.055 * Math.pow(v, 1 / 2.4) - 0.055) * 255 + 0.5);
        }
      },
      sign: function (n) {
        return (n < 0 ? -1 : 1);
      },
      signPow: function (val, exp) {
        return Blurhash._internal.utils.sign(val) * Math.pow(Math.abs(val), exp);
      },
    },
    base83: {
      digitCharacters: ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '#', '$', '%', '*', '+', ',', '-', '.', ':', ';', '=', '?', '@', '[', ']', '^', '_', '{', '|', '}', '~'],
      decode83: function (str) {
        var value = 0;
        for (var i = 0; i < str.length; i++) {
          var c = str[i];
          var digit = Blurhash._internal.base83.digitCharacters.indexOf(c);
          value = value * 83 + digit;
        }
        return value;
      }
    },
    decode: {
      decodeDC: function (value) {
        var intR = value >> 16;
        var intG = (value >> 8) & 255;
        var intB = value & 255;
        return [
          Blurhash._internal.utils.sRGBToLinear(intR),
          Blurhash._internal.utils.sRGBToLinear(intG),
          Blurhash._internal.utils.sRGBToLinear(intB)
        ];
      },
      decodeAC: function (value, maximumValue) {
        var quantR = Math.floor(value / (19 * 19));
        var quantG = Math.floor(value / 19) % 19;
        var quantB = value % 19;
        return [
          Blurhash._internal.utils.signPow((quantR - 9) / 9, 2.0) * maximumValue,
          Blurhash._internal.utils.signPow((quantG - 9) / 9, 2.0) * maximumValue,
          Blurhash._internal.utils.signPow((quantB - 9) / 9, 2.0) * maximumValue
        ];
      },
      validateBlurhash: function (blurhash) {
        if (!blurhash || blurhash.length < 6) {
          throw new Error('The blurhash string must be at least 6 characters');
        }

        var sizeFlag = Blurhash._internal.base83.decode83(blurhash[0]);

        var numY = Math.floor(sizeFlag / 9) + 1;
        var numX = sizeFlag % 9 + 1;

        if (blurhash.length !== 4 + 2 * numX * numY) {
          throw new Error("blurhash length mismatch: was ".concat(blurhash.length, " but it should be ").concat(4 + 2 * numX * numY));
        }
      }
    }
  },
  isValid: function (blurhash) {
    try {
      Blurhash._internal.decode.validateBlurhash(blurhash);
    } catch (error) {
      return {
        result: false,
        errorReason: error.message
      };
    }

    return {
      result: true
    };
  },
  decodeBlurhash: function (blurhash, width, height, punch) {
    Blurhash._internal.decode.validateBlurhash(blurhash);

    punch = punch | 1;

    var sizeFlag = Blurhash._internal.base83.decode83(blurhash[0]);
    var numY = Math.floor(sizeFlag / 9) + 1;
    var numX = sizeFlag % 9 + 1;

    var quantisedMaximumValue = Blurhash._internal.base83.decode83(blurhash[1]);
    var maximumValue = (quantisedMaximumValue + 1) / 166;

    var colors = new Array(numX * numY);

    for (var i = 0; i < colors.length; i++) {
      var value;
      if (i === 0) {
        value = Blurhash._internal.base83.decode83(blurhash.substring(2, 6));
        colors[i] = Blurhash._internal.decode.decodeDC(value);
      } else {
        value = Blurhash._internal.base83.decode83(blurhash.substring(4 + i * 2, 6 + i * 2));
        colors[i] = Blurhash._internal.decode.decodeAC(value, maximumValue * punch);
      }
    }

    var bytesPerRow = width * 4;
    var pixels = new Uint8ClampedArray(bytesPerRow * height);

    for (var y = 0; y < height; y++) {
      for (var x = 0; x < width; x++) {
        var r = 0;
        var g = 0;
        var b = 0;

        for (var j = 0; j < numY; j++) {
          for (var k = 0; k < numX; k++) {
            var basis =
              Math.cos(Math.PI * x * k / width) *
              Math.cos(Math.PI * y * j / height);
            var color = colors[k + j * numX];
            r += color[0] * basis;
            g += color[1] * basis;
            b += color[2] * basis;
          }
        }

        var intR = Blurhash._internal.utils.linearTosRGB(r);
        var intG = Blurhash._internal.utils.linearTosRGB(g);
        var intB = Blurhash._internal.utils.linearTosRGB(b);

        pixels[4 * x + 0 + y * bytesPerRow] = intR;
        pixels[4 * x + 1 + y * bytesPerRow] = intG;
        pixels[4 * x + 2 + y * bytesPerRow] = intB;
        pixels[4 * x + 3 + y * bytesPerRow] = 255; // alpha
      }
    }
    return pixels;
  },
  pixelsToCanvas: function (pixels, width, height) {
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    const imageData = new ImageData(pixels, width, height);
    ctx.putImageData(imageData, 0, 0);
    return canvas;
  },
  pixelsToBase64: function (pixels, width, height, quality, type) {
    const canvas = Blurhash.pixelsToCanvas(pixels, width, height);
    return canvas.toDataURL(type || 'image/jpeg', quality || 1);
  },
  onPixelsToBlobSrc: function (cb, pixels, width, height, quality, type) {
    const canvas = Blurhash.pixelsToCanvas(pixels, width, height);
    canvas.toBlob(function (blob) {
      var newImg = document.createElement('img'),
        url = URL.createObjectURL(blob);
      newImg.onload = function () {
        URL.revokeObjectURL(url);
      };
      cb(url);
    }, type || 'image/jpeg', quality || 1);
  }
};

window.ecbUtils = {
  getViewport: function () {
    return {
      height: window.innerHeight || document.documentElement.clientHeight,
      width: window.innerWidth || document.documentElement.clientWidth
    };
  },
  getNodeAttribute: function (node, attrName) {
    var attr = node.attributes.getNamedItem(attrName);
    return attr ? attr.value : null;
  },
  decodeHashForNode: function (attr, pseudoImage , cb) {
    pseudoImage = pseudoImage ? pseudoImage : attr.node;

    setTimeout(function () {
      if (pseudoImage.complete === false) {
        var pixels = Blurhash.decodeBlurhash(attr.hash, attr.width, attr.height);

        Blurhash.onPixelsToBlobSrc(function (src) {
          pseudoImage.complete === true ? cb(null) : cb(src);
        }, pixels, attr.width, attr.height);
      } else {
        cb(null);
      }
    }, 200);
  },
  extractNode: function (node) {
    var hash = ecbUtils.getNodeAttribute(node, 'data-blurhash');

    if (!hash || ecbUtils.isImageCached(node.src)) {
      return null;
    }

    var width = Number(ecbUtils.getNodeAttribute(node, 'data-ow'));
    var height = Number(ecbUtils.getNodeAttribute(node,'data-oh'));
    var srcset = ecbUtils.getNodeAttribute(node, 'srcset') || ecbUtils.getNodeAttribute(node, 'data-srcset');

    if (isNaN(width) || width === 0 || isNaN(height) || height === 0) {
      return null;
    }

    return { hash: hash, width: width, height: height, srcset: srcset, node: node }
  },
  isAncestorsVisible: function (node) {
    var isVisible = true;

    do {
      var styles = getComputedStyle(node);
      isVisible = !(Number(styles.opacity) === 0 || styles.display === 'none');
      node = node.parentElement;
    } while (node !== null && isVisible === true);

    return isVisible;
  },
  isImageCached: function (src) {
    var img = new Image();
    img.src = src;
    var complete = img.complete;
    img.src = '';
    return complete;
  },
  isInViewport: function (node) {
    var rect = node.getBoundingClientRect();
    var viewport = ecbUtils.getViewport();

    return (
      rect.top > 0 && rect.bottom > 0 // Full height visible
      || (rect.bottom - viewport.height > 0 && rect.top < 0) // Larger than vertical viewport
      || (rect.top - viewport.height) * -1 > 0 && rect.bottom - viewport.height > 0 // Only top visible
      || (rect.bottom - viewport.height) * -1 > 0 && rect.top + viewport.height < 0 // Only bottom visible
      // TODO horizontal viewport check
    );
  },
  placeholders: {},
  placeholderBase64: function (width, height) {
    var key = width + '_' + height;

    if (ecbUtils.placeholders[key]) {
      return ecbUtils.placeholders[key];
    }

    var canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    return ecbUtils.placeholders[key] = canvas.toDataURL('image/webp', 1);
  }
}
<!-- prettier-ignore-end -->
