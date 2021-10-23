/**
 * @param {string} src
 * @return {boolean}
 */
export function isImageCached(src) {
  const img = new window.Image();
  img.src = src;

  const complete = img.complete;
  img.src = '';

  return complete;
}
