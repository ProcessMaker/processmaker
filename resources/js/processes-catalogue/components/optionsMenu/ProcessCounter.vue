<template>
  <!-- <div class="process-counter"> -->
    <!-- <div>
      <p class="process-counter-text">
        {{ $t('Cases Started') }}
      </p>
      <p class="process-counter-total">
        {{ count }}
      </p>
    </div>
    <img
      class="process-counter-image"
      src="/img/launchpad-images/iconCounter.svg"
      alt="icon Counter"
    > -->
    <div class="d-flex align-items-center">
      <img class="thumb-16px" src="/img/launchpad-images/icons/Apple Fruit.svg"></img>
      <span class="text-summary">{{ count }} {{ $t('Cases started') }}</span>
      <img class="thumb-16px-1" src="/img/launchpad-images/icons/mini-chart-52-75.svg"></img>
      <span class="text-summary">{{ completed }} {{ $t('Completed') }}</span>
      <img class="thumb-16px" src="/img/launchpad-images/icons/mini-chart-49-51.svg"></img>
      <span class="text-summary">{{ inProgress }} {{ $t('In Progress') }}</span>
    </div>

  <!-- </div> -->
</template>
<script>
export default {
  props: ["process"],
  data() {
    return {
      count: 0,
      completed: 0,
      inProgress: 0,
    };
  },
  mounted() {
    this.fetch();
    console.log("Process object: ", this.process);
    this.completed = this.process.counts.completed;
    this.inProgress =  this.process.counts.in_progress;
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
  color: #556271;
  margin: 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 32px;
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

.text-summary {
    color: #556271;
    font-size: 16px;
    font-weight: 400;
    vertical-align: middle;
  }
.thumb-16px {
  width: 18px;
  height: 18px;
}
.thumb-16px-1 {
  width: 18px;
  height: 18px;
  background-color: #4EA075;
}
</style>
