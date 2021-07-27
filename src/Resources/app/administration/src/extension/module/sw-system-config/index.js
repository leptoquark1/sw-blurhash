Shopware.Component.override('sw-system-config', {

  computed: {
    salesChannelSwitchable() {
     return this.domain === 'EyecookBlurhash.config'
       ? false
       : this.$props.salesChannelSwitchable;
    },
  },
});
