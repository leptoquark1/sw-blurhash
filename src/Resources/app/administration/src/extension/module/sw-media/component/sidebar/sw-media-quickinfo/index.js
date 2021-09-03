import template from './sw-media-quickinfo.html.twig';
import './sw-media-quickinfo.scss';

const { Component, Mixin } = Shopware;

Component.override('sw-media-quickinfo', {
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
      ecbValidationError: undefined,
    }
  },

  mounted() {
    this.ecbValidate();
  },

  watch: {
    item: function () {
      this.ecbValidate(true);
    },
  },

  computed: {
    canEcbGenerate() {
      return (this.isEcbValid === true || this.hasItemBlurhash) && this.hasEcbGenerated === false;
    },

    canEcbValidate() {
      return this.isEcbValid === null && this.hasItemBlurhash === false;
    },

    isEcbLoading() {
      return this.isEcbValidating || this.isEcbGenerating;
    },

    generationText() {
      return this.hasItemBlurhash
        ? this.$t('ecBlurhash.general.generation.refresh')
        : this.$t('ecBlurhash.general.generation.start');
    },

    generationHelpText() {
      return this.hasItemBlurhash //
        ? this.$t('ecBlurhash.general.generation.media.helpForce')
        : this.$t('ecBlurhash.general.generation.media.help')
    }
  },

  methods: {
    async ecbValidate(refresh) {
      if (this.isEcbLoading || (!refresh && this.isEcbValid !== null)) {
        return;
      }

      this.isEcbValidating = true;
      try {
        const result = await this.ecbValidationApiService.fetchValidateByMediaId(this.item.id);

        this.isEcbValid = result.valid;
        this.ecbValidationError = result.message;
      } catch (err) {
        this.createNotificationError({ message: err.message });
      }

      setTimeout(() => {
        this.isEcbValidating = false;
      }, 300);
    },

    async onEcbGenerateActionClick() {
      if (this.isEcbLoading || this.hasEcbGenerated) {
        return;
      }

      this.isEcbGenerating = true;

      try {
        await this.ecbGenerationApiService.fetchGenerateByMediaId(this.item.id);
        this.hasEcbGenerated = true;
      } catch (err) {
        let message = err.message;

        if (err.response?.status === 424) {
          const data = err.response.data;
          message = this.$t(`ecBlurhash.errors.${data.errors[0]?.code}`);
        }

        this.createNotificationError({ message });
      }

      this.isEcbGenerating = false;
    },
  },
});
