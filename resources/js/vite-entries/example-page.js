/**
 * Example Vite entry: runs after layout (app.js) is loaded.
 * Mounts a minimal Vue 2 app for testing.
 * Edit ExamplePage.vue to see HMR in action (no full page reload).
 */

import ExamplePage from './ExamplePage.vue';

new window.Vue({
  el: '#vite-example-app',
  components: { ExamplePage },
  template: '<ExamplePage />',
});
