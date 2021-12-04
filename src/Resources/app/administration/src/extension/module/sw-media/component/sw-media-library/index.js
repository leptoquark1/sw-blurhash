const { Component } = Shopware;

Component.override('sw-media-library', {
  mounted() {
    window.addEventListener('eyecook_blurhash_generated', this.refreshList);
  },

  beforeDestroy() {
    window.removeEventListener('eyecook_blurhash_generated', this.refreshList);
  },
});
