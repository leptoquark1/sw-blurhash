import { decodeHashByHashMeta, extractBlurhashMetaFromNode, getMutationObserver, getNodeAttribute, isAncestorsVisible, isInViewport } from './utils/dom';
import { isImageCached } from './utils/image';

window.ecbHelper = {
  getMutationObserver,
  getNodeAttribute,
  isAncestorsVisible,
  isImageCached,
  isInViewport,
  extractBlurhashMetaFromNode,
  decodeHashByHashMeta,
}
