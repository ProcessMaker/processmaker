<template>
  <div class="spacing-class" v-if="!enableCollapse">
    <span class="title">
      {{ $t('Started Cases') }}
    </span>
    <div>
      <p class="process-counter-total">
        {{ count }}
      </p>
    </div>
    <div class="charts">
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
  <div v-else class="d-flex align-items-center">
    <img class="thumb-size" :src="`/img/launchpad-images/icons/${processIcon}.svg`" :alt="$t('No Image')"></img>
    <span class="text-summary">{{ count }} {{ $t('Cases started') }}</span>
    <div class="charts">
      <mini-pie-chart
        :count="process.counts.completed"
        :total="process.counts.total"
        :name="$t('In Progress')"
        color="#4EA075"
      />
      <mini-pie-chart
        :count="process.counts.in_progress"
        :total="process.counts.total"
        :name="$t('Completed')"
        color="#478FCC"
      />
      <div >
        <div
          v-if="iconWizardTemplate"
          class="icon-wizard-class"
          @click="getHelperProcess"
        >
          <img style="margin-left: 8px;"
            src="../../../../img/wizard-icon.svg"
            :alt="$t('Guided Template Icon')"
          >
      </div>
      </div>
    </div>
  </div>
</template>
<script>
import MiniPieChart from "../MiniPieChart.vue";
import WizardHelperProcessModal from "../../../components/templates/WizardHelperProcessModal.vue";

export default {
  components: {
    MiniPieChart,
    WizardHelperProcessModal,
  },
  props: ["process", "enableCollapse", "iconWizardTemplate"],
  data() {
    return {
      count: 0,
      completed: 0,
      inProgress: 0,
      processIcon: 'Default Icon',
      completedCollapsed: 0,
      inProgressCollapsed: 0,
    };
  },
  mounted() {
    this.fetch();
    this.getProcessImage();
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
    getProcessImage() {
      if(this.process.launchpad) {
        const propertiesString = this.process.launchpad["properties"];
        const propertiesObject = JSON.parse(propertiesString);
        this.processIcon = propertiesObject.icon;
      }
      
      return this.processIcon ? this.processIcon : 'Default Icon';
    },
    getHelperProcess() {
      this.$parent.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
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
