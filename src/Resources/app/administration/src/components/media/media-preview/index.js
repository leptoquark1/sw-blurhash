import template from './media-preview.html.twig'
import './media-preview.scss'

const { Component, Mixin } = Shopware;

Component.register('ecb-media-preview', {
  template,

  mixins: [
    Mixin.getByName('ecb-blurhash'),
  ],

  data() {
    return {
      blurhashSrc: null,
    }
  },

  props: {
    item: {
      type: Object,
      required: true
    }
  },

  watch: {
    item: function () {
      this.refreshSrc();
    },
  },

  methods: {
    async refreshSrc() {
      return this.itemBlurhashAsImageSrc()
                 .then(hash => this.blurhashSrc = hash)
                 .catch();
    },
  },

  async mounted() {
    this.refreshSrc();
  },
});
