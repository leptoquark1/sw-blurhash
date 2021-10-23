import { decodeBlurhash, pixelsToBlobUrl } from '../blurhash/decode';
/**
 * @callback StringOrNullCallback
 * @param {( null | string )} value
 */

/**
 * @typedef {{node: HTMLImageElement, width: number, srcset: string, hash: string, height: number}} HashMeta
 */

/**
 * @param {MutationCallback} handler
 * @return {MutationObserver}
 */
export function getMutationObserver(handler) {
  MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
  return new MutationObserver(handler);
}

/**
 * @return {{width: number, height: number}}
 */
export function getViewport() {
  return {
    height: window.innerHeight || window.document.documentElement.clientHeight,
    width: window.innerWidth || window.document.documentElement.clientWidth
  };
}

/**
 * @param {HTMLElement} node
 * @return {boolean}
 */
export function isInViewport(node) {
  const rect = node.getBoundingClientRect();
  const viewport = getViewport();

  return (
    rect.top > 0 && rect.bottom > 0 // Full height visible
    || (rect.bottom - viewport.height > 0 && rect.top < 0) // Larger than vertical viewport
    || (rect.top - viewport.height) * -1 > 0 && rect.bottom - viewport.height > 0 // Only top visible
    || (rect.bottom - viewport.height) * -1 > 0 && rect.top + viewport.height < 0 // Only bottom visible
    // TODO horizontal viewport check
  );
}

/**
 * @param {Element} node
 * @param {string} attrName
 * @return {string | null}
 */
export function getNodeAttribute(node, attrName) {
  const attr = node.attributes.getNamedItem(attrName);

  return attr ? attr.value : null;
}

/**
 * @param {HTMLElement} node
 * @return {boolean}
 */
export function isAncestorsVisible(node) {
  let isVisible = true;

  do {
    const styles = getComputedStyle(node);
    isVisible = !(window.Number(styles.opacity) === 0 || styles.display === 'none');
    node = node.parentElement;
  } while (node !== null && isVisible === true);

  return isVisible;
}

/**
 * @param {HTMLImageElement} node
 * @return {(null | HashMeta)}
 */
export function extractBlurhashMetaFromNode(node) {
  const hash = getNodeAttribute(node, 'data-blurhash');
  const src = getNodeAttribute(node, 'data-src');

  if (!hash) {
    return null;
  }

  const width = Number(getNodeAttribute(node, 'data-ow'));
  const height = Number(getNodeAttribute(node, 'data-oh'));
  const srcset = getNodeAttribute(node, 'srcset') || getNodeAttribute(node, 'data-srcset');

  if (isNaN(width) || width === 0 || isNaN(height) || height === 0) {
    if (src) {
      node.src = src;
    }

    return null;
  }

  return { node, hash, width, height, srcset };
}

/**
 * @param {HashMeta} meta
 * @param {StringOrNullCallback} callback
 * @param {HTMLImageElement} pseudoImage
 * @param {number} delay
 * @return {void}
 */
export function decodeHashByHashMeta(meta, pseudoImage, callback, delay = 200) {
  pseudoImage = pseudoImage ? pseudoImage : meta.hash;

  setTimeout(function () {
    if (pseudoImage.complete === false) {
      const pixels = decodeBlurhash(meta.hash, meta.width, meta.height);

      pixelsToBlobUrl(pixels, meta.width, meta.height, function (src) {
        pseudoImage.complete === true ? callback(null) : callback(src);
      });
    } else {
      callback(null);
    }
  }, delay);
}
