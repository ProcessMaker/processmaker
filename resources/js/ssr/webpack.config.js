var path = require('path');

module.exports = {
  entry: path.join(__dirname, 'ssr-renderer.es6.js'),
  target: 'node',
  output: {
    path: path.join(__dirname),
    filename: 'ssr-renderer.js'
  },
}

/*
node node_modules/webpack/bin/webpack.js --config=resources/js/ssr/webpack.config.js
node resources/js/ssr/ssr-renderer.js
*/
