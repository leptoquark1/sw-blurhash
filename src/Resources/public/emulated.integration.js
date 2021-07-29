window.ecBlurhash = {
  imageNodes: [],
  imageNodesPostponed: [],
  loadingImages: {},

  addImageNode: function (nodeAttr) {
    if (document.readyState === 'interactive') {
      // Process sync, otherwise we need to wait for the actual images
      ecBlurhash.decodeHashForImage(nodeAttr);
    } else {
      ecBlurhash.imageNodes.unshift(nodeAttr);
    }
  },

  addPostponedImageNode: function (nodeAttr) {
    ecBlurhash.imageNodesPostponed.unshift(nodeAttr);
  },

  decodeHashForImage: function (attr) {
    var pseudoImg = ecBlurhash.outsourceLoadingImage(attr);

    ecbUtils.decodeHashForNode(attr, pseudoImg, function (src) {
      if (src === null) {
         if (!!ecbUtils.getNodeAttribute(attr.node, 'data-blurhash')) {
           ecBlurhash.onFinalImageLoad(attr).call(attr.node);
         }
        return;
      }

      ecBlurhash.prepareNodeForBlurhash(attr, src);
    });
  },

  onPlaceholderImageLoad: function (attr) {
    return function () {
      this.setAttribute('data-ecb-p', '1');
      this.onload = ecBlurhash.onFinalImageLoad(attr);

      if (ecbUtils.isAncestorsVisible(attr.node) && ecbUtils.isInViewport(attr.node)) {
        ecBlurhash.addImageNode(attr);
        // Preload the image (but not by the original element)
        ecBlurhash.outsourceLoadingImage(attr);
      } else {
        // Postpone all other images
        ecBlurhash.addPostponedImageNode(attr);
      }
    }
  },

  onBlurhashImageLoad: function (attr) {
    return function () {
      this.setAttribute('data-ecb-p', '2');
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
      attr.node.style.backgroundImage = null;
      this.removeAttribute('data-ecb-p');
      this.removeAttribute('data-blurhash');
      this.removeAttribute('data-ow');
      this.removeAttribute('data-oh');
    }
  },

  outsourceLoadingImage: function (attr) {
    if (ecBlurhash.loadingImages.hasOwnProperty(attr.hash)) {
      return ecBlurhash.loadingImages[attr.hash];
    }

    var img = new Image();
    img.onload = function () {
      img.onload = null;
      attr.node.srcset = attr.srcset;
      attr.node.src = ecbUtils.getNodeAttribute(attr.node, 'data-src');
      attr.node.removeAttribute('data-srcset');
    }
    img.srcset = attr.srcset;

    return ecBlurhash.loadingImages[attr.hash] = img;
  },

  prepareNodeForBlurhash: function (attr, src) {
    setTimeout(function () {
      attr.node.onload = ecBlurhash.onBlurhashImageLoad(attr);
      attr.node.src = src;
      attr.node.parentElement.classList.add('ecb-loading');
    }, 1);
  },

  prepareNodeForPlaceholder: function (attr) {
    setTimeout(function () {
      attr.node.onload = ecBlurhash.onPlaceholderImageLoad(attr);
      attr.node.src = ecbUtils.placeholderBase64(attr.width, attr.height);
    }, 1);
  },

  prepareNode: function (node) {
    var attr = ecbUtils.extractNode(node);
    if (!attr || !attr.hash) return;

    ecBlurhash.prepareNodeForPlaceholder(attr);
  },

  mutationHandler: function (mutations) {
    for (var mx = 0; mx < mutations.length; mx++) {
      var mutation = mutations[mx];

      for (var anx = 0; anx < mutation.addedNodes.length; anx++) {
        var node = mutation.addedNodes[anx];
        var isImageNode = node.nodeType === Node.ELEMENT_NODE && node.tagName === 'IMG';

        if (isImageNode && node.hasAttribute('data-ecb-p') === false) {
          ecBlurhash.prepareNode(node);
        }
      }
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
