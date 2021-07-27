import template from './sw-media-quickinfo.html.twig';

Shopware.Component.override('sw-media-quickinfo', {
  template,

  computed: {
    ecbBluredImage() {
      const { blurhash, height, width} = this.item.metaData;

      if (!blurhash || !Blurhash.isValid(blurhash)) {
        return null;
      }

      const pixels = Blurhash.decodeBlurhash(blurhash, width, height);
      return Blurhash.pixelsToBase64(pixels, width, height);
    },
  },

  methods: {
  },
});
