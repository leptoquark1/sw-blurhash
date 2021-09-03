import template from './sw-media-preview-v2.html.twig';
import './sw-media-preview-v2.scss';

const { Component, Mixin } = Shopware;

Component.override('sw-media-preview-v2', {
  template,

  mixins: [
    Mixin.getByName('ecb-blurhash'),
  ],

  data() {
    return {
      isBlurhashPreview: false,
    };
  },

  methods: {
    toggleBlurhashPreview() {
      this.isBlurhashPreview = !this.isBlurhashPreview;
    },
  },

  computed: {
    isBlurhashPreviewApplicable() {
      return this.width > 100;
    },
  },
});
