const { Mixin } = Shopware;
const { pick, get } = Shopware.Utils.object;

Mixin.register('ecb-blurhash', {
  methods: {
    hasBlurhash(item) {
      return !!get(item, 'metaData.blurhash', false)
    },

    pickBlurhashMetaData(item) {
      return pick(item.metaData, ['blurhash', 'hashOriginHeight', 'hashOriginWidth']);
    },

    blurhashToBase64(item) {
      const data = this.pickBlurhashMetaData(item);
      const pixels = Blurhash.decodeBlurhash(data.blurhash, data.hashOriginWidth, data.hashOriginHeight);
      return Blurhash.pixelsToBase64(pixels, data.hashOriginWidth, data.hashOriginHeight);
    },

    async itemBlurhashAsImageSrc() {
      return new Promise((resolve, reject) => {
        if (!this.hasItemBlurhash) {
          reject();
        }

        setTimeout(() => {
          resolve(this.blurhashToBase64(this.item));
        })

      });
    }
  },

  computed: {
    itemBlurhash() {
      return get(this.item || this.trueSource, 'metaData.blurhash', '');
    },

    hasItemBlurhash() {
      return this.hasBlurhash(this.item || this.trueSource);
    },

    hasItemValidBlurhashMeta() {
      const data = this.pickBlurhashMetaData(this.item || this.trueSource);
      return data && data.blurhash && data.hashOriginHeight && data.hashOriginWidth;
    },
  }
});
