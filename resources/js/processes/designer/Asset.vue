<template>
  <b-card-text class="asset">
    <span>
      <i
        :class="icon"
        :style="{color: color}"
      />
      <p>{{ $t(asset_name) }}</p>
    </span>
  </b-card-text>
</template>

<script>
export default {
  props: [
    "color",
    "asset_name",
    "icon"
  ],
  data() {
    return { };
  },
};
</script>

<style scoped>
.asset {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 4px;
  flex: 1 0 0;
  align-self: stretch;
  margin-bottom: 10px;
}
i {
  font-size: 32px;
}
</style>
