window.ecBlurhash = {
  config: {
    decodeDelay: 200
  },
  imageNodes: [],
  imageNodesPostponed: [],
  placeholders: {},
  placeholderBase64: function placeholderBase64(width, height) {
    var key = width + '_' + height;

    if (ecBlurhash.placeholders[key]) {
      return ecBlurhash.placeholders[key];
    }

    var canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    return ecBlurhash.placeholders[key] = canvas.toDataURL('image/webp', 1);
  },
  isImageCached: function isImageCached(src) {
    var img = new Image();
    img.src = src;
    var complete = img.complete;
    img.src = '';
    return complete;
  },
  isInViewport: function isInViewport(node) {
    return true; // TODO Skipped, because issues with the result of this function

    var rect = node.getBoundingClientRect();
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
    return (rect.top >= 0 && rect.top <= viewportHeight || rect.bottom <= 0 && rect.bottom >= viewportHeight * -1) && (rect.left >= 0 && rect.left <= viewportWidth || rect.right <= 0 && rect.right >= viewportWidth * -1);
  },
  isAncestorsVisible: function isAncestorsVisible(node) {
    var isVisible = true;

    do {
      var styles = getComputedStyle(node);
      isVisible = !(Number(styles.opacity) === 0 || styles.display === 'none');
      node = node.parentElement;
    } while (node !== null && isVisible === true);

    return isVisible;
  },
  prepareNode: function prepareNode(node) {
    var hashAttr = node.attributes.getNamedItem('data-blurhash');

    if (!hashAttr || ecBlurhash.isImageCached(node.src)) {
      return;
    }

    var hash = hashAttr.value;
    var widthAttr = node.attributes.getNamedItem('data-ow');
    var heightAttr = node.attributes.getNamedItem('data-oh');
    var width = widthAttr ? Number(widthAttr.value) : 0;
    var height = heightAttr ? Number(heightAttr.value) : 0;

    if (isNaN(width) || width === 0 || isNaN(height) || height === 0) {
      return;
    }

    var placeholder = node.cloneNode(true);
    placeholder.srcset = '';

    node.onload = function () {
      this.removeAttribute('data-blurhash');
      node.style.height = null;
      placeholder.remove();
      placeholder = null;
      this.onload = null;
    };

    placeholder.src = ecBlurhash.placeholderBase64(width, height);
    placeholder.setAttribute('data-blurhash-placeholder', '1');
    node.style.height = '0';
    node.parentNode.insertBefore(placeholder, node);
    ecBlurhash.imageNodes.unshift({
      node: node,
      hash: hash,
      height: height,
      width: width,
      placeholder: placeholder
    });
  },
  mutationHandler: function mutationHandler(mutations, observer) {
    for (var mx = 0; mx < mutations.length; mx++) {
      var mutation = mutations[mx];

      for (var anx = 0; anx < mutation.addedNodes.length; anx++) {
        var node = mutation.addedNodes[anx];
        var isImageNode = node.nodeType === Node.ELEMENT_NODE && node.tagName === 'IMG';

        if (isImageNode && node.hasAttribute('data-blurhash-placeholder') === false) {
          ecBlurhash.prepareNode(node);
        }
      }
    }
  },
  decodeHashForImage: function decodeHashForImage(nodeData) {
    setTimeout(function () {
      if (nodeData.placeholder.hasAttribute('data-blurhash')) {
        var pixels = Blurhash.decodeBlurhash(nodeData.hash, nodeData.width, nodeData.height);

        if (nodeData.placeholder.hasAttribute('data-blurhash')) {
          Blurhash.onPixelsToBlobSrc(function (src) {
            nodeData.placeholder.src = src;
          }, pixels, nodeData.width, nodeData.height);
        }

        nodeData.placeholder.removeAttribute('data-blurhash');
      }
    }, ecBlurhash.config.decodeDelay);
  },
  onDomInteractive: function onDomInteractive() {
    while (ecBlurhash.imageNodes.length) {
      var nodeData = ecBlurhash.imageNodes.pop();

      if (ecBlurhash.isAncestorsVisible(nodeData.node) && (document.readyState === 'complete' || ecBlurhash.isInViewport(nodeData.node))) {
        ecBlurhash.decodeHashForImage(nodeData);
      } else {
        ecBlurhash.imageNodesPostponed.unshift(nodeData);
      }
    }
  },
  onDomComplete: function onDomComplete() {
    while (ecBlurhash.imageNodesPostponed.length) {
      var nodeData = ecBlurhash.imageNodesPostponed.pop();
      ecBlurhash.decodeHashForImage(nodeData);
    }
  }
};
