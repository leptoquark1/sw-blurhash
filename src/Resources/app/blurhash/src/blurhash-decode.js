import { decodeBlurhash, pixelsToBase64, pixelsToBlobUrl, pixelsToCanvas } from './blurhash/decode';

window.pixelsToCanvas = pixelsToCanvas;
window.pixelsToBase64 = pixelsToBase64;
window.pixelsToBlobUrl = pixelsToBlobUrl;
window.decodeBlurhash = decodeBlurhash;
