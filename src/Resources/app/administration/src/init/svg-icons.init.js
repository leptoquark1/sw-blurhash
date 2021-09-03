import iconComponents from '../assets/icons/generator';

const { Component } = Shopware;

export default (() => {
  return iconComponents.map((component) => {
    return Component.register(component.name, component);
  });
})();
