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
<!-- prettier-ignore-end -->
