import template from './quick-action.html.twig'

const { Component } = Shopware;

Component.register('ecb-quick-action', {
  template,

  props: {
    disabled: {
      type: Boolean,
      required: false,
      default: false,
    },
    title: {
      type: String,
      required: false,
    },
    icon: {
      type: String,
      required: true,
    },
    text: {
      type: String,
      required: true,
    },
    color: {
      type: String,
      required: false,
    },
  },

  computed: {
    actionClasses() {
      return {
        'sw-media-sidebar__quickaction--disabled': this.disabled,
      };
    }
  },

  methods: {
    onActionClick() {
      this.disabled === false && this.$emit('click');
    }
  },
});
