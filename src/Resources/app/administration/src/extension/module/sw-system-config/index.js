const { Component } = Shopware;

/**
 * As long as the configuration is of global scope, this component must be extended.
 * This is currently the only way to change the property "salesChannelSwitchable" and
 * thus hide the SalesChannel switch.
 *
 * This will produce a Vue warning
 */
Component.override('sw-system-config', {
  computed: {
    salesChannelSwitchable() {
      return this.domain === 'EyecookBlurhash.config'
        ? false
        : this.$props.salesChannelSwitchable;
    },
  },
});
