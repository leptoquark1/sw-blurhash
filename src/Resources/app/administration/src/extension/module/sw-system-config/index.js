Shopware.Component.override('sw-system-config', {

  computed: {
    salesChannelSwitchable() {
     return this.domain === 'EcBlurHash.config'
       ? false
       : this.$props.salesChannelSwitchable;
    },
  },
});
