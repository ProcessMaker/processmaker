<template>
  <div>
    <!-- TODO -->
    <!-- <base-chart v-model="chart" ref="baseChart" :height="chartHeight" :additionalPmql="additionalPmql"></base-chart> -->
    <div class="card-chart">
    <base-chart ref="baseChart"></base-chart>
    </div>
  </div>
</template>
<script>
import BaseChart from "../BaseChart.vue";

export default {
  components: {
    BaseChart,
  },
  // props: ["permission", "isDocumenterInstalled", "currentUserId", "process"],
  data() {
    return {}
  }
  }
  </script>
  