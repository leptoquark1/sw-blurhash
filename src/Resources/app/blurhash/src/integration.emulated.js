import { decodeHashByHashMeta, extractBlurhashMetaFromNode, getMutationObserver, getNodeAttribute, isAncestorsVisible, isInViewport } from './utils/dom';

(function () {
  const imageNodesPostponed = [];
  const loadingImages = new Object(null);
  const decoded = new Object(null);
  const cbOnDecode = new Object(null);

  /**
   * @param {HashMeta} meta
   * @return {HTMLImageElement}
   */
  function createPseudoImageFromMeta(meta) {
    const key = meta.srcset;

    function applySrcDataAttributes() {
      // Set original node src and srcset from data attributes
      meta.node.srcset = meta.srcset;
      meta.node.src = getNodeAttribute(meta.node, 'data-src');
      meta.node.removeAttribute('data-srcset');
    }

    // Check if there is an image just like the given srcset
    if (loadingImages.hasOwnProperty(key)) {
      const prevImg = loadingImages[key];

      if (!loadingImages[key].complete) {
        // Stack existing callback to have it call another for this node
        const prevOnload = prevImg.onload;
        prevImg.onload = function () {
          prevOnload.call(this);
          applySrcDataAttributes.call(this);
        };
      } else {
        // When previous image is complete we are done here too
        applySrcDataAttributes(meta);
      }

      return prevImg;
    }

    // Create a new pseudo image that refer to our original when finished loading
    const img = new Image();
    img.onload = function () {
      img.onload = null;
      applySrcDataAttributes(meta);
    }
    // take the place of the original and load the image by it's srcset
    img.srcset = meta.srcset;

    return loadingImages[key] = img;
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
      this.removeAttribute('data-blurhash');
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

      // Apply original srcset to trigger native browser to fetch original src
      if (!this.srcset) {
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

    if ((previousResult === null || typeof previousResult === 'string')) {
      // Encode with this hash did run before; call specific node callback
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

  /**
   * @param {HTMLImageElement} node
   */
  function prepareNode(node) {
    // Try to extract Blurhash meta from this Image Node
    const meta = extractBlurhashMetaFromNode(node);
    // Early return when invalid data
    if (!meta || !meta.hash) return;

    // Add final callback to the target node
    meta.node.onload = function () {
      this.onload = onFinalImageLoad(meta);
    }

    if (isAncestorsVisible(meta.node) && isInViewport(meta.node)) {
      decodeHashForImage(meta);
      // Preload the image (but not by the original element)
      createPseudoImageFromMeta(meta);
    } else {
      // Postpone all other images
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
   * @return {void}
   */
  function cleanUp() {
    for (let hashProp in loadingImages) {
      if (loadingImages.hasOwnProperty(hashProp)) {
        let img = loadingImages[hashProp];
        if (img.complete) {
          img = null;
        }
      }
    }
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
    getMutationObserver(mutationHandler).observe(document.body, { childList: true, subtree: true });
  }

  /**
   * @return {void}
   */
  function readyStateChangeListener() {
    const state = window.document.readyState;

    if (state === 'interactive') {
      onDomInteractive();
    } else if (state === 'complete') {
      cleanUp();
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
