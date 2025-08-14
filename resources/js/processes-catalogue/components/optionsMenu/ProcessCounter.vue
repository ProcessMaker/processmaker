<template>
  <div>
    <span class="title">
      {{ $t('Started Cases') }}
    </span>
    <div>
      <p class="process-counter-total">
        {{ count }}
      </p>
    </div>
    <div
      v-if="!isStagesSummary"
      class="charts"
    >
      <mini-pie-chart
        :count="process.counts.in_progress"
        :total="process.counts.total"
        :name="$t('In Progress')"
        color="#4EA075"
      />
      <mini-pie-chart
        :count="process.counts.completed"
        :total="process.counts.total"
        :name="$t('Completed')"
        color="#478FCC"
      />
    </div>
  </div>
</template>
<script>
import MiniPieChart from "../MiniPieChart.vue";

export default {
  components: {
    MiniPieChart,
  },
  props: ["process", "enableCollapse"],
  data() {
    return {
      count: 0,
    };
  },
  computed: {
    isStagesSummary() {
      return this.process.stagesSummary.length > 0;
    },
  },
  mounted() {
    this.fetch();
  },
  methods: {
    fetch() {
      ProcessMaker.apiClient
        .get(`requests/${this.process.id}/count`)
        .then((response) => {
          this.count = response.data.meta.total;
        })
        .catch(() => {
          this.count = 0;
        });
    },
  },
};
</script>
<style scoped>
.process-counter {
  display: flex;
  justify-content: space-between;
  width: 294px;
  height: 90px;
  padding: 17px 24px;
  border-radius: 16px;
  border: 1px solid #daebf7;
  background: #e7f9ff;
}
.process-counter-total {
  color: #4C545C;
  margin: 5px 0px 16px 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 55px;
  font-weight: 600;
  line-height: 55px;
  letter-spacing: -0.02em;
  text-align: left;
}
.process-counter-text {
  color: #556271;
  margin: 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  font-weight: 400;
  line-height: 19.07px;
  letter-spacing: -0.02em;
  text-align: left;

}
.process-counter-image {
  width: 56px;
  height: 56px;
}
.thumb-size {
  width: 22px;
  height: 22px;
}

.title {
  color: #1572C2;
  font-size: 18px;
  font-weight: 700;
  line-height: 27px;
  letter-spacing: -0.02em;
}
.text-summary {
  color: #B1B8BF;
  font-size: 16px;
  font-weight: 400;
  vertical-align: middle;
  font-style: italic;
  margin-right: 5px;
}
.charts {
  display: flex;
  align-items: center;
}
.spacing-class {
  margin-top: 10px;
  padding-left: 15px;
}
.icon-wizard-class {
  border-left: 1px solid rgba(0, 0, 0, 0.125);
  z-index: 5;
}
</style>
