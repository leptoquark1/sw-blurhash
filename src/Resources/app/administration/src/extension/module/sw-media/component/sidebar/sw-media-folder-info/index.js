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

        const message = all
          ? 'ecBlurhash.general.generation.folder.notificationOnForceGenerate'
          : 'ecBlurhash.general.generation.folder.notificationOnGenerate'

        this.createNotificationInfo({ title: 'Blurhash', message: this.$tc(message, 0, { folderName: this.mediaFolder.name }) });
      } catch (err) {
        let message = err.message;

        if (err.response?.status === 424) {
          const data = err.response.data;
          const code = data.errors[0]?.code || 'UnknownError';
          message = this.$t(`ecBlurhash.errors.${code}`,);
        }
        this.createNotificationError({ message });
      }

      this.isEcbGenerating = false;
    },
  }
});
