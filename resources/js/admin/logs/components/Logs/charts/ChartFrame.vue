<template>
  <div class="chart-frame">
    <slot />
  </div>
</template>

<style scoped>
.chart-frame {
  position: relative;
  width: 100%;
  height: 220px;
}
.chart-frame ::v-deep > div {
  width: 100%;
  height: 220px;
  position: relative;
}
</style>
