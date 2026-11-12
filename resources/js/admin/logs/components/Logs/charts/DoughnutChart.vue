<script>
import { Doughnut } from 'vue-chartjs';
import { chartBoxStyles } from './chartTheme';

export default {
  extends: Doughnut,
  props: {
    data: {
      type: Object,
      default: () => ({ labels: [], datasets: [] }),
    },
    options: {
      type: Object,
      default: () => ({}),
    },
    styles: {
      type: Object,
      default: () => ({ ...chartBoxStyles }),
    },
  },
  mounted() {
    this.render();
  },
  watch: {
    data: {
      deep: true,
      handler() {
        this.render();
      },
    },
  },
  methods: {
    render() {
      this.renderChart(this.data, this.options);
    },
  },
};
</script>
