import { decodeHashByHashMeta, extractBlurhashMetaFromNode, getMutationObserver, getNodeAttribute, isAncestorsVisible, isInViewport } from './utils/dom';

(function () {
  const imageNodesPostponed = [];
  const loadingImages = new Object(null);
  const decoded = new Object(null);
  const cbOnDecode = new Object(null);

  /**
   * @param {HashMeta} meta
   * @return {string}
   */
  function getKeyFromMeta(meta) {
    return meta.srcset + meta.node.sizes;
  }

  /**
   * @param {HashMeta} meta
   */
  function applySrcDataAttributes(meta) {
    // Set original node src and srcset from data attributes
    meta.node.srcset = meta.srcset;
    meta.node.src = getNodeAttribute(meta.node, 'data-src');
    meta.node.removeAttribute('data-srcset');
    meta.node.removeAttribute('data-src');
  }

  /**
   * @param {HashMeta} meta
   * @return {HTMLImageElement}
   */
  function createPseudoImageFromMeta(meta) {
    const key = getKeyFromMeta(meta);

    // Check if there is an image just like the given element (group same)
    if (loadingImages.hasOwnProperty(key)) {
      const prevImg = loadingImages[key].image;

      if (loadingImages[key].node.isEqualNode(meta.node)) {
        // Prevent the handling of exact same nodes
        return prevImg;
      }

      if (!prevImg.complete) {
        // Stack existing callback to have it call another for this node
        const prevOnload = prevImg.onload;
        prevImg.onload = function () {
          prevOnload.call(this);
          applySrcDataAttributes(meta);
        };
      } else {
        // When previous image is complete we are done here too
        applySrcDataAttributes(meta);
      }

      return prevImg;
    }

    // Create a new pseudo image that refer to our original when finished loading
    const img = new Image(meta.node.width, meta.node.height);
    img.onload = function () {
      img.onload = null;
      applySrcDataAttributes(meta);
    }
    // take the place of the original and load the image by its srcset
    img.sizes = meta.node.sizes;
    img.srcset = meta.srcset;

    // Memoize
    loadingImages[key] = { image: img, node: meta.node };

    return img;
  }

  /**
   * @param {HashMeta} meta
   * @return {Function}
   */
  function onFinalImageLoad(meta) {
    return function () {
      // Prevent any further callbacks
      this.onload = null;

      meta.node.parentElement.classList.remove('ecb-loading');

      // Reset all attributes and clean up
      this.removeAttribute('data-ecb-bh');
      this.removeAttribute('data-ow');
      this.removeAttribute('data-oh');
    }
  }

  /**
   * @param {HashMeta} meta
   * @return {Function}
   */
  function onBlurhashImageLoad(meta) {
    return function () {

      // Indicate that this have a valid Blurhash src
      this.setAttribute('data-ecb-bh', '1');
      this.onload = onFinalImageLoad(meta);

      // Apply original srcset to trigger native browser to fetch original src in fallback case
      const key = getKeyFromMeta(meta);
      if (loadingImages.hasOwnProperty(key) && loadingImages[key].image.complete && !this.srcset) {
        this.srcset = meta.srcset;
      }
    }
  }

  /**
   * @param {HashMeta } meta
   * @param {string } src
   */
  function prepareNodeForBlurhash(meta, src) {
    meta.node.onload = onBlurhashImageLoad(meta);
    meta.node.src = src;
    meta.node.parentElement.classList.add('ecb-loading');
  }

  /**
   * @param {HashMeta} meta
   * @return {StringOrNullCallback}
   */
  function onDecode(meta) {
    return function (src) {

      // When no global entry yet
      if (Array.isArray(decoded[meta.hash])) {
        // add to global decode list
        decoded[meta.hash] = src;

        // Run callbacks of each affected node for this hash subsequently
        const cbs = cbOnDecode[meta.hash] || [];
        while (cbs.length) {
          cbs.pop()(src);
        }
      }

      if (src !== null) {
        // When valid src from decoding apply to node
        prepareNodeForBlurhash(meta, src);
      } else if (!!getNodeAttribute(meta.node, 'data-blurhash')) {
        // Otherwise skip to last step to apply final image
        onFinalImageLoad(meta).call(meta.node);
      }
    }
  }

  /**
   * @param {HashMeta} meta
   */
  function decodeHashForImage(meta) {
    // Check if encoding did or is currently running
    // Is null or string when it did run
    // An Array of node objects when it is already running
    const previousResult = decoded[meta.hash];

    // Decouple image loading from this node
    const pseudoImage = createPseudoImageFromMeta(meta);

    if (pseudoImage.complete) {
      return
    }

    if (previousResult === null || typeof previousResult === 'string') {
      // Decode with this key already run; call specific node callback
      onDecode(meta)(previousResult);
    } else if (Array.isArray(previousResult) && previousResult.includes(meta.node) === false) {
      // Encode with this hash is currently running
      // Add this node to result list
      previousResult.push(meta.node);
      // Add this not to decode callbacks
      if (Array.isArray(cbOnDecode[meta.hash]) === false) {
        cbOnDecode[meta.hash] = [];
      }
      cbOnDecode[meta.hash].push(onDecode(meta))
    } else {
      // Add this node to global pending list for this hash
      decoded[meta.hash] = [meta.node];
      // Start decoding for this hash meta
      decodeHashByHashMeta(meta, pseudoImage, onDecode(meta), 50);
    }
  }

  /**
   * @param {HashMeta} meta
   */
  function addPostponedImageNode(meta) {
    if (window.document.readyState === 'complete') {
      // When Dom is ready we can decode synchronous
      decodeHashForImage(meta);
    } else {
      // when not we postpone decoding
      imageNodesPostponed.unshift(meta);
    }
  }

  function isImageExcluded(node) {
    return (node.parentElement && node.parentElement.classList.contains('image-zoom-container')) || node.classList.contains('js-load-img');
  }

  /**
   * @param {HTMLImageElement} node
   */
  function prepareNode(node) {
    // Try to extract Blurhash meta from this Image Node
    const meta = extractBlurhashMetaFromNode(node);
    // Early return when invalid data
    if (!meta || !meta.hash) return;

    // Some images are excluded
    if (isImageExcluded(meta.node)) {
      applySrcDataAttributes(meta);
      onFinalImageLoad(meta).call(meta.node);
      return;
    }

    // Add final callback to the target node
    meta.node.onload = function () {
      this.onload = onFinalImageLoad(meta);
    }

    if (isAncestorsVisible(meta.node) && isInViewport(meta.node)) {
      decodeHashForImage(meta);
    } else {
      addPostponedImageNode(meta);
    }
  }

  /**
   * @param {Element} node
   * @return {boolean}
   */
  function isImgNodeSuitable(node) {
    // Only node elements have tagName property set that determinate a valid image
    return node.nodeType === Node.ELEMENT_NODE && node.tagName === 'IMG' && !getNodeAttribute(node, 'data-ecb-bh')
  }

  function findImageNodesInChildren(nodeList) {
    nodeList.forEach(function (node) {
      if (isImgNodeSuitable(node)) {
        // Handle only image nodes
        return prepareNode(node);
      }

      if (window.document.readyState === 'complete' && node.hasChildNodes()) {
        // Digging deeper in this node
        findImageNodesInChildren(node.childNodes);
      }
    })
  }

  /**
   * @param {MutationCallback} mutations
   */
  function mutationHandler(mutations) {
    setTimeout(function () {
      for (let mx = 0; mx < mutations.length; mx++) {
        findImageNodesInChildren(mutations[mx].addedNodes);
      }
    }, 1);
  }

  /**
   * @return {void}
   */
  function onDomInteractive() {
    // Run all postponed images
    while (imageNodesPostponed.length) {
      decodeHashForImage(imageNodesPostponed.pop());
    }

    // Listen for further mutations of the DOM to be able to handle upcoming images
    getMutationObserver(mutationHandler).observe(document.body, { childList: true, subtree: true, attributeFilter: ['data-blurhash'] });
  }

  /**
   * @return {void}
   */
  function readyStateChangeListener() {
    const state = window.document.readyState;

    if (state === 'interactive') {
      onDomInteractive();
    }
  }

  /**
   * @return {void}
   */
  function collectAndDecodeByTagName() {
    const nodes = window.document.getElementsByTagName('img');

    if (nodes.length <= 0) {
      return;
    }

    for (let i = 0; i < nodes.length; i++) {
      const node = nodes.item(i);
      // Expect image suitable because it was gathered with tag name selector
      prepareNode(node);
    }
  }

  window.ecbDecode = collectAndDecodeByTagName;
  window.ecbReadyStateChangeListener = readyStateChangeListener;
})();
