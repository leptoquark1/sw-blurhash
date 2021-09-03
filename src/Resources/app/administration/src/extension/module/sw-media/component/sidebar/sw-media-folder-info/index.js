import template from './sw-media-folder-info.html.twig';

const { Component, Mixin } = Shopware;

Component.override('sw-media-folder-info', {
  template,

  inject: ['ecbValidationApiService', 'ecbGenerationApiService'],

  mixins: [
    Mixin.getByName('notification'),
    Mixin.getByName('ecb-blurhash'),
  ],

  data() {
    return {
      isEcbValid: null,
      isEcbValidating: false,
      isEcbGenerating: false,
      hasEcbGenerated: false,
    }
  },

  mounted() {
    this.ecbValidate();
  },

  computed: {
    canEcbGenerate() {
      return this.isEcbValid === true && this.hasEcbGenerated === false;
    },

    isEcbLoading() {
      return this.isEcbValidating || this.isEcbGenerating;
    }
  },

  methods: {
    async onGenerateMissingActionClick() {
      return this.ecbGenerate(false);
    },

    async onGenerateAllActionClick() {
      return this.ecbGenerate(true);
    },

    async ecbValidate() {
      if (this.isEcbLoading || this.isEcbValid !== null) {
        return;
      }

      this.isEcbValidating = true;

      try {
        const result = await this.ecbValidationApiService.fetchValidateByFolderId(this.mediaFolder.id);
        this.isEcbValid = result.valid;
      } catch (err) {
        this.createNotificationError({ message: err.message });
      }

      setTimeout(() => {
        this.isEcbValidating = false;
      }, 300);
    },

    async ecbGenerate(all) {
      if (this.isEcbLoading || this.hasEcbGenerated) {
        return;
      }

      this.isEcbGenerating = true;

      try {
        await this.ecbGenerationApiService.fetchGenerateByFolderId(this.mediaFolder.id, all);
        this.hasEcbGenerated = true;
      } catch (err) {
        this.createNotificationError({ message: err.message });
      }

      this.isEcbGenerating = false;
    },
  }
});
