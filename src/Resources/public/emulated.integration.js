window.ecBlurhash = {
  imageNodes: [],
  imageNodesPostponed: [],
  loadingImages: {},
  decoded: {},
  cbOnDecode: {},

  addImageNode: function (nodeAttr) {
    if (document.readyState === 'interactive' || document.readyState === 'complete') {
      // Process sync, otherwise we need to wait for the actual images
      ecBlurhash.decodeHashForImage(nodeAttr);
    } else {
      ecBlurhash.imageNodes.unshift(nodeAttr);
    }
  },

  addPostponedImageNode: function (nodeAttr) {
    if (document.readyState === 'complete') {
      ecBlurhash.decodeHashForImage(nodeAttr);
    } else {
      ecBlurhash.imageNodesPostponed.unshift(nodeAttr);
    }
  },

  addCbOnDecode(hash, cb) {
    if (Array.isArray(ecBlurhash.cbOnDecode[hash]) === false) {
      ecBlurhash.cbOnDecode[hash] = [];
    }
    ecBlurhash.cbOnDecode[hash].push(cb)
  },

  decodeHashForImage: function (attr) {
    var pseudoImg = ecBlurhash.outsourceLoadingImage(attr);
    var decoded = ecBlurhash.decoded[attr.hash];
    var onDecode = ecBlurhash.onDecode(attr);

    if ((decoded === null || typeof decoded === 'string')) {
      onDecode(decoded);
    } else if (decoded === true) {
      ecBlurhash.addCbOnDecode(attr.hash, onDecode);
    } else {
      ecBlurhash.decoded[attr.hash] = true;
      ecbUtils.decodeHashForNode(attr, pseudoImg, onDecode);
    }
  },

  onDecode: function(attr) {
    return function(src) {
      if (ecBlurhash.decoded[attr.hash] === true) {
        ecBlurhash.decoded[attr.hash] = src;

        var cbs = ecBlurhash.cbOnDecode[attr.hash] || [];
        while (cbs.length) {
          cbs.pop()(src);
        }
      }

      if (src !== null) {
        ecBlurhash.prepareNodeForBlurhash(attr, src);
      } else if (!!ecbUtils.getNodeAttribute(attr.node, 'data-blurhash')) {
         ecBlurhash.onFinalImageLoad(attr).call(attr.node);
      }
    }
  },

  onBlurhashImageLoad: function (attr) {
    return function () {
      this.setAttribute('data-ecb-bh', '1');
      this.onload = ecBlurhash.onFinalImageLoad(attr);

      if (!this.srcset) {
        this.srcset = attr.srcset;
      }
    }
  },

  onFinalImageLoad: function (attr) {
    return function () {
      this.onload = null;

      attr.node.parentElement.classList.remove('ecb-loading');
      this.removeAttribute('data-ecb-bh');
      this.removeAttribute('data-blurhash');
      this.removeAttribute('data-ow');
      this.removeAttribute('data-oh');
    }
  },

  onOriginSrcLoaded: function (attr) {
    attr.node.srcset = attr.srcset;
    attr.node.src = ecbUtils.getNodeAttribute(attr.node, 'data-src');
    attr.node.removeAttribute('data-srcset');
  },

  outsourceLoadingImage: function (attr) {
    var key = attr.srcset;
    if (ecBlurhash.loadingImages.hasOwnProperty(key)) {
      var prevImg = ecBlurhash.loadingImages[key];

      if (prevImg.complete) {
        ecBlurhash.onOriginSrcLoaded(attr);
      } else {
        prevImg.onload = ecbUtils.nestedCallback(prevImg.onload, function () {
          ecBlurhash.onOriginSrcLoaded(attr);
        });
      }

      return prevImg;
    }

    var img = new Image();
    img.onload = function () {
      img.onload = null;
      ecBlurhash.onOriginSrcLoaded(attr);
    }
    img.srcset = attr.srcset;

    return ecBlurhash.loadingImages[key] = img;
  },

  prepareNodeForBlurhash: function (attr, src) {
    attr.node.onload = ecBlurhash.onBlurhashImageLoad(attr);
    attr.node.src = src;
    attr.node.parentElement.classList.add('ecb-loading');
  },

  prepareNode: function (node) {
    var attr = ecbUtils.extractNode(node);
    if (!attr || !attr.hash) return;

    attr.node.onload = function () {
      this.onload = ecBlurhash.onFinalImageLoad(attr);
    }

    if (ecbUtils.isAncestorsVisible(attr.node) && ecbUtils.isInViewport(attr.node)) {
      ecBlurhash.addImageNode(attr);
      // Preload the image (but not by the original element)
      ecBlurhash.outsourceLoadingImage(attr);
    } else {
      // Postpone all other images
      ecBlurhash.addPostponedImageNode(attr);
    }
  },

  findImageNodesInChildren(nodeList,) {
    nodeList.forEach(function (node) {
      if (
        node.nodeType === Node.ELEMENT_NODE
        && node.tagName === 'IMG'
        && node.hasAttribute('data-ecb-p') === false
      ) {
        return ecBlurhash.prepareNode(node);
      }

      if (document.readyState === 'complete' && node.hasChildNodes()) {
        ecBlurhash.findImageNodesInChildren(node.childNodes);
      }
    })
  },

  mutationHandler: function (mutations) {
    for (var mx = 0; mx < mutations.length; mx++) {
      ecBlurhash.findImageNodesInChildren(mutations[mx].addedNodes);
    }
  },

  readyStateChangeListener: function () {
    if (document.readyState === 'interactive') {
      ecBlurhash.onDomInteractive();
    } else if (document.readyState === 'complete') {
      ecBlurhash.onDomComplete();
    }
  },

  onDomInteractive: function () {
    while (ecBlurhash.imageNodes.length) {
      ecBlurhash.decodeHashForImage(ecBlurhash.imageNodes.pop());
    }
  },

  onDomComplete: function () {
    while (ecBlurhash.imageNodesPostponed.length) {
      ecBlurhash.decodeHashForImage(ecBlurhash.imageNodesPostponed.pop());
    }

    ecBlurhash.cleanUp();
  },

  cleanUp: function () {
    for (var hashProp in ecBlurhash.loadingImages) {
      if (ecBlurhash.loadingImages.hasOwnProperty(hashProp)) {
        var img = ecBlurhash.loadingImages[hashProp];
        if (img.complete) {
          img = null;
        }
      }
    }
  }
};
