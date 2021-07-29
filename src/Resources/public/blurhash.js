<!-- prettier-ignore-start -->
window.Blurhash = {
  _internal: {
    utils: {
      D: 3294.6,
      E: 269.025,
      PI: Math.PI,
      PI2: Math.PI * 2,
      sRGBToLinear: function(value) {
        return value > 10.31475 ? Math.pow(value / Blurhash._internal.utils.E + 0.052132, 2.4) : value / Blurhash._internal.utils.D;
      },
      linearTosRGB: function(v) {
        return ~~(v > 0.00001227 ? Blurhash._internal.utils.E * Math.pow(v, 0.416666) - 13.025 : v * Blurhash._internal.utils.D + 1);
      },
      sign: function sign(n) {
        return n < 0 ? -1 : 1;
      },
      signPow: function(val, exp) {
        return Blurhash._internal.utils.sign(val) * Math.pow(Math.abs(val), exp);
      },
      signSqr: function(x) {
        return (x < 0 ? -1 : 1) * x * x;
      },
      fastCos: function(x) {
        x += Blurhash._internal.utils.PI / 2;
        while (x > Blurhash._internal.utils.PI) {
          x -= Blurhash._internal.utils.PI2;
        }
        var cos = 1.27323954 * x - 0.405284735 * Blurhash._internal.utils.signSqr(x);
        return 0.225 * (Blurhash._internal.utils.signSqr(cos) - cos) + cos;
      }
    },
    base83: {
      digit: '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz#$%*+,-.:;=?@[]^_{|}~',
      decode83: function decode83(str, start, end) {
        var value = 0;
        while (start < end) {
          value *= 83;
          value += Blurhash._internal.base83.digit.indexOf(str[start++]);
        }
        return value;
      }
    }
  },
  decodeBlurhash: function decodeBlurhash(blurHash, width, height, punch) {
    var sizeFlag = Blurhash._internal.base83.decode83(blurHash, 0, 1);

    var numX = sizeFlag % 9 + 1;
    var numY = ~~(sizeFlag / 9) + 1;
    var size = numX * numY;
    var maximumValue = (Blurhash._internal.base83.decode83(blurHash, 1, 2) + 1) / 13446 * (punch | 1);
    var colors = new Float64Array(size * 3);

    var value = Blurhash._internal.base83.decode83(blurHash, 2, 6);

    colors[0] = Blurhash._internal.utils.sRGBToLinear(value >> 16);
    colors[1] = Blurhash._internal.utils.sRGBToLinear(value >> 8 & 255);
    colors[2] = Blurhash._internal.utils.sRGBToLinear(value & 255);
    var i = 0,
        j = 0,
        x = 0,
        y = 0,
        r = 0,
        g = 0,
        b = 0,
        basis = 0,
        basisY = 0,
        colorIndex = 0,
        pixelIndex = 0,
        yh = 0,
        xw = 0;

    for (i = 1; i < size; i++) {
      value = Blurhash._internal.base83.decode83(blurHash, 4 + i * 2, 6 + i * 2);
      colors[i * 3] = Blurhash._internal.utils.signSqr(~~(value / (19 * 19)) - 9) * maximumValue;
      colors[i * 3 + 1] = Blurhash._internal.utils.signSqr(~~(value / 19) % 19 - 9) * maximumValue;
      colors[i * 3 + 2] = Blurhash._internal.utils.signSqr(value % 19 - 9) * maximumValue;
    }

    var bytesPerRow = width * 4;
    var pixels = new Uint8ClampedArray(bytesPerRow * height);

    for (y = 0; y < height; y++) {
      yh = Blurhash._internal.utils.PI * y / height;

      for (x = 0; x < width; x++) {
        r = 0;
        g = 0;
        b = 0;
        xw = Blurhash._internal.utils.PI * x / width;

        for (j = 0; j < numY; j++) {
          basisY = Blurhash._internal.utils.fastCos(yh * j);

          for (i = 0; i < numX; i++) {
            basis = Blurhash._internal.utils.fastCos(xw * i) * basisY;
            colorIndex = (i + j * numX) * 3;
            r += colors[colorIndex] * basis;
            g += colors[colorIndex + 1] * basis;
            b += colors[colorIndex + 2] * basis;
          }
        }

        pixelIndex = 4 * x + y * bytesPerRow;
        pixels[pixelIndex] = Blurhash._internal.utils.linearTosRGB(r);
        pixels[pixelIndex + 1] = Blurhash._internal.utils.linearTosRGB(g);
        pixels[pixelIndex + 2] = Blurhash._internal.utils.linearTosRGB(b);
        pixels[pixelIndex + 3] = 255; // alpha
      }
    }

    return pixels;
  },
  pixelsToCanvas: function(pixels, width, height) {
    var canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    var ctx = canvas.getContext('2d');
    var imageData = new ImageData(pixels, width, height);
    ctx.putImageData(imageData, 0, 0);
    return canvas;
  },
  pixelsToBase64: function(pixels, width, height, quality, type) {
    var canvas = Blurhash.pixelsToCanvas(pixels, width, height);
    return canvas.toDataURL(type || 'image/jpeg', quality || 1);
  },
  onPixelsToBlobSrc: function(cb, pixels, width, height, quality, type) {
    var canvas = Blurhash.pixelsToCanvas(pixels, width, height);
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
  decodeHashForNode: function (attr, pseudoImage, cb) {
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
    var height = Number(ecbUtils.getNodeAttribute(node, 'data-oh'));
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
