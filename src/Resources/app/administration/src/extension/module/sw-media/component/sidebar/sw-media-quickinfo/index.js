import template from './sw-media-quickinfo.html.twig';

const { pick } = Shopware.Utils.object;

Shopware.Component.override('sw-media-quickinfo', {
  template,

  computed: {
    hasBlurhash() {
      const data = pick(this.item.metaData, ['blurhash', 'hashOriginHeight', 'hashOriginWidth']);
      return data.blurhash && data.hashOriginHeight && data.hashOriginWidth
    },

    blurhashImg() {
      if (!this.hasBlurhash) {
        return;
      }
      const data = pick(this.item.metaData, ['blurhash', 'hashOriginHeight', 'hashOriginWidth']);

      const pixels = Blurhash.decodeBlurhash(data.blurhash, data.hashOriginWidth, data.hashOriginHeight);
      return Blurhash.pixelsToBase64(pixels, data.hashOriginWidth, data.hashOriginHeight);
    }
  },
});
