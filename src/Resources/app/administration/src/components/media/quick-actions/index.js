import template from './quick-actions.html.twig'
import './quick-actions.scss'

const { Component } = Shopware;

Component.register('ecb-quick-actions', {
  template,

  props: {
    isValid: {
      type: Boolean,
      required: false,
      default: null,
    },
    isValidating: {
      type: Boolean,
      required: false,
      default: false,
    },
    validationErrorText: {
      type: String,
      required: false,
    },
    validationError: {
      type: String,
      required: false,
    },
    disableTitle: {
      type: Boolean,
      required: false,
      default: false,
    }
  },
});
