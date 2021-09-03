const path = require('path');

function resolve(dir) {
  return path.join(__dirname, '..', dir);
}

module.exports = (baseConf) => {
  // Exclude the plugin's icons from being loaded via a url-loader
  baseConf.config.module.rules.forEach((rule) => {
    if (rule.loader === 'url-loader') {
      if (!rule.exclude) {
        rule.exclude = [];
      }
      rule.exclude.push(resolve('src/assets/icons/svg'));
    }
  });

  // Add svg-inline-loader for the plugin icons
  return {
    module: {
      rules: [
        {
          test: /\.svg$/,
          include: [
            resolve('src/assets/icons/svg')
          ],
          loader: 'svg-inline-loader',
          options: {
            removeSVGTagAttrs: false
          }
        }
      ]
    }
  };
};
