import { fastCos, signSqr } from './ac';
import { decode83 } from './base83';
import { linearTosRGB, sRGBToLinear } from './color';
import { PI } from './constants';

/**
 * @param {string} blurhash The base83 string of the encoded image
 * @param {number} width Width of the resulting image (Must match the one used for encoding)
 * @param {number} height Height of the resulting image (Must match the one used for encoding)
 * @param {number} punch The factor to improve the contrast; Must be greater than 1
 *
 * @return {Uint8ClampedArray} The encoded pixel array
 */
export function decodeBlurhash(blurhash, width, height, punch = 1) {
  const sizeFlag = decode83(blurhash, 0, 1);

  const numX = sizeFlag % 9 + 1;
  const numY = ~~(sizeFlag / 9) + 1;
  const size = numX * numY;
  const maximumValue = (decode83(blurhash, 1, 2) + 1) / 13446 * (punch);
  const colors = new Float64Array(size * 3);
  let value = decode83(blurhash, 2, 6);


  colors[0] = sRGBToLinear(value >> 16);
  colors[1] = sRGBToLinear(value >> 8 & 255);
  colors[2] = sRGBToLinear(value & 255);

  let i = 0;
  for (i = 1; i < size; i++) {
    value = decode83(blurhash, 4 + i * 2, 6 + i * 2);
    colors[i * 3] = signSqr(~~(value / (19 * 19)) - 9) * maximumValue;
    colors[i * 3 + 1] = signSqr(~~(value / 19) % 19 - 9) * maximumValue;
    colors[i * 3 + 2] = signSqr(value % 19 - 9) * maximumValue;
  }

  const bytesPerRow = width * 4;
  const pixels = new Uint8ClampedArray(bytesPerRow * height);

  for (let y = 0; y < height; y++) {
    let yh = PI * y / height;

    for (let x = 0; x < width; x++) {
      let r = 0;
      let g = 0;
      let b = 0;
      let xw = PI * x / width;

      for (let j = 0; j < numY; j++) {
        let basisY = fastCos(yh * j);

        for (i = 0; i < numX; i++) {
          const basis = fastCos(xw * i) * basisY;
          let colorIndex = (i + j * numX) * 3;
          r += colors[colorIndex] * basis;
          g += colors[colorIndex + 1] * basis;
          b += colors[colorIndex + 2] * basis;
        }
      }

      let pixelIndex = 4 * x + y * bytesPerRow;
      pixels[pixelIndex] = linearTosRGB(r);
      pixels[pixelIndex + 1] = linearTosRGB(g);
      pixels[pixelIndex + 2] = linearTosRGB(b);
      pixels[pixelIndex + 3] = 255;
    }
  }

  return pixels;
}

/**
 * @param {Uint8ClampedArray} pixels The result of decode function
 * @param {number} width Width of the resulting image (Must match the one used for encoding)
 * @param {number} height Height of the resulting image (Must match the one used for encoding)
 *
 * @return {HTMLCanvasElement} A canvas with applied input pixels
 */
export function pixelsToCanvas(pixels, width, height) {
  const canvas = window.document.createElement('canvas');

  canvas.width = width;
  canvas.height = height;
  canvas.getContext('2d')
        .putImageData(new ImageData(pixels, width, height), 0, 0);

  return canvas;
}

/**
 * @param {Uint8ClampedArray} pixels The result of decode function
 * @param {number} width Width of the resulting image (Must match the one used for encoding)
 * @param {number} height number Height of the resulting image (Must match the one used for encoding)
 * @param {string} type Image format; Default is 'image/jpeg'
 * @param {number} quality number A Number between 0 and 1 indicating the image quality; Needed when type is image/jpeg or image/webp; Default is 1
 *
 * @return {string} The Base64 representation of the input pixels
 */
export function pixelsToBase64(pixels, width, height, quality = 1, type = 'image/jpeg') {
  const canvas = pixelsToCanvas(pixels, width, height);
  return canvas.toDataURL(type, quality);
}

/**
 * @callback blobUrlCallback
 * @param {string|null} url The Url to the blob image
 */

/**
 * @param {Uint8ClampedArray} pixels The result of blurhash decode function
 * @param {number} width number Width of the resulting image (Must match the one used for encoding)
 * @param {number} height number Height of the resulting image (Must match the one used for encoding)
 * @param {blobUrlCallback} callback A function that will be called with the created url to the blob image (or null on failure)
 */
export function pixelsToBlobUrl(pixels, width, height, callback) {
  const canvas = pixelsToCanvas(pixels, width, height);

  canvas.toBlob(function (blob) {
    if (blob === null) {
      return callback(null);
    }
    const img = window.document.createElement('img');
    const url = URL.createObjectURL(blob);

    img.onload = function () {
      URL.revokeObjectURL(url);
    };

    callback(url);
  });
}
