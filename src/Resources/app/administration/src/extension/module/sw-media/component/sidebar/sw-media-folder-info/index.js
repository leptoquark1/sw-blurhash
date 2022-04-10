import template from './sw-media-folder-info.html.twig';

const { Component, Mixin } = Shopware;

Component.override('sw-media-folder-info', {
  template,

  inject: ['ecbValidationApiService', 'ecbGenerationApiService', 'ecbRemovalApiService', 'systemConfigApiService'],

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

    async onExcludeActionClick() {
      if (this.isEcbLoading) {
        return;
      }

      const currentConfig = await this.systemConfigApiService.getValues('EyecookBlurhash.config');
      const excluded = currentConfig['EyecookBlurhash.config.excludedFolders'] ?? [];

      if (excluded.includes(this.mediaFolder.id) === false) {
        excluded.push(this.mediaFolder.id);
      }

      await this.systemConfigApiService.saveValues({
        'EyecookBlurhash.config.excludedFolders': excluded,
      });

      return this.ecbValidate(true);
    },

    async onIncludeActionClick() {
      if (this.isEcbLoading) {
        return;
      }

      const currentConfig = await this.systemConfigApiService.getValues('EyecookBlurhash.config');
      const excluded = currentConfig['EyecookBlurhash.config.excludedFolders'] ?? [];

      if (excluded.includes(this.mediaFolder.id)) {
        excluded.splice(excluded.indexOf(this.mediaFolder.id), 1)
      }

      await this.systemConfigApiService.saveValues({
        'EyecookBlurhash.config.excludedFolders': excluded,
      });

      return this.ecbValidate(true);
    },

    async onRemoveAllActionClick() {
      if (this.isEcbLoading) {
        return;
      }

      this.isEcbGenerating = true;

      try {
        await this.ecbRemovalApiService.fetchRemoveByFolderId(this.mediaFolder.id);

        this.hasEcbGenerated = false;

        this.createNotificationInfo({
          title: 'Blurhash',
          message: this.$tc('ecBlurhash.general.removal.folder.notificationOnRemove', 0, { folderName: this.mediaFolder.name })
        });

        setTimeout(() => {
          this.$nextTick(() => {
            this.$emit('media-item-replaced');
          });
        }, 2000);
      } catch (err) {
        this.createNotificationError({ message: err.message });
      }

      this.isEcbGenerating = false;
    },

    async ecbValidate(force = false) {
      if (this.isEcbLoading || (force === false && this.isEcbValid !== null)) {
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
