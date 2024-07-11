<template>
  <div class="chart">
    <div class="pie" :style="style"></div>
    <div>{{ percent.toLocaleString() }}% {{ name }}</div>
  </div>
</template>

<script>
export default {
  props: {
    name: {
      type: String,
      default: null,
    },
    count: {
      type: Number,
      default: 0,
    },
    total: {
      type: Number,
      default: 0,
    },
    color: {
      type: String,
      default: '#000',
    },
  },
  computed: {
    style() {
      return {
        backgroundImage: `conic-gradient(${this.color} 0%, ${this.color} ${this.percent}%, white ${this.percent}%, white 100%)`,
        borderColor: this.color,
      };
    },
    percent() {
      return this.total === 0 ? 0 : Math.round((this.count / this.total) * 100);
    },
  }
};
</script>

<style scoped lang="scss">
@import '~styles/variables';
.chart {
  display: flex;
  align-items: center;
  font-style: italic;
  color: $secondary;

  div {
    margin-right: 8px;
  }
}
.pie {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid;
}
</style>