import '../../../css/vite/app.css';
import Vue from 'vue';
import DemoBadge from '../components/DemoBadge.vue';

const mountEl = document.getElementById('vite-demo-app');

if (mountEl) {
  // eslint-disable-next-line no-new
  new Vue({
    el: mountEl,
    components: {
      DemoBadge,
    },
    data() {
      return {
        title: 'Vite + Vue 2',
        ticks: 0,
      };
    },
    mounted() {
      this._timer = setInterval(() => {
        this.ticks += 1;
      }, 1000);
    },
    beforeDestroy() {
      clearInterval(this._timer);
    },
  });
}
