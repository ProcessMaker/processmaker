<template>
  <div v-if="!enableCollapse">
    <span class="title">
      {{ $t('Started Cases') }}
    </span>
    <div>
      <p class="process-counter-total">
        {{ count }}
      </p>
    </div>
    <div class="d-flex align-items-center">
      <status-summary 
      :completedIcon="completedIcon" 
      :inProgressIcon="inProgressIcon" 
      :completed="completed" 
      :inProgress="inProgress" 
    />
    </div>
  </div>
  <div v-else class="d-flex align-items-center">
    <img class="thumb-size" :src="`/img/launchpad-images/icons/${processIcon}.svg`" :alt="$t('No Image')"></img>
    <span class="text-summary">{{ count }} {{ $t('Cases started') }}</span>
    <status-summary 
      :completedIcon="completedIcon" 
      :inProgressIcon="inProgressIcon" 
      :completed="completed" 
      :inProgress="inProgress" 
    />
  </div>
</template>
<script>
import CustomIcon from "../utils/CustomIcon.vue"
import StatusSummary from '../../components/StatusSummary.vue';

export default {
  components: {
    CustomIcon,
    StatusSummary,
  },
  props: ["process", "enableCollapse"],
  data() {
    return {
      count: 0,
      completed: 0,
      inProgress: 0,
      processIcon: null,
    };
  },
  mounted() {
    this.fetch();
    this.percentCalcs();
    this.getProcessImage();
  },
  computed: {
    completedIcon() {
      return this.getIconName(this.completed);
    },
    inProgressIcon() {
      return this.getIconName(this.inProgress);
    },
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
    percentCalcs() {
      if (this.process.counts.total === 0 || !this.process.counts.total) {
        this.completed = 0;
        this.inProgress = 0;
      } else {
        this.completed = Math.round((this.process.counts.completed * 100) / this.process.counts.total) || 0;
        this.inProgress = Math.round((this.process.counts.in_progress * 100) / this.process.counts.total) || 0;
      }
    },
    getProcessImage() {
      const propertiesString = this.process.launchpad["properties"];
      const propertiesObject = JSON.parse(propertiesString);
      this.processIcon = propertiesObject.icon;
      return this.processIcon ? this.processIcon : null;
    },
    getIconName(value) {
      if (value === 0) {
        return "mini-chart-0";
      } else if (value >= 1 && value <= 25) {
        return "mini-chart-1-25";
      } else if (value >= 26 && value <= 48) {
        return "mini-chart-26-48";
      } else if (value >= 49 && value <= 51) {
        return "mini-chart-49-51";
      } else if (value >= 52 && value <= 75) {
        return "mini-chart-52-75";
      } else if (value >= 76 && value <= 99) {
        return "mini-chart-76-99";
      } else {
        return "mini-chart-100";
      }
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
  margin: 5px 0px 5px 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 55px;
  font-weight: 700;
  line-height: 43.58px;
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
  width: 20px;
  height: 20px;
}
.d-flex.align-items-center > * {
  margin-right: 8px;
}
.title {
  color: #1572C2;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -0.02em;
}
.text-summary {
  color: #B1B8BF;
  font-size: 16px;
  font-weight: 400;
  vertical-align: middle;
  font-style: italic;
}
</style>
