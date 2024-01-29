<template>
  <b-card-text class="asset">
    <span>
      <i
        :class="icon"
        :style="{color: color}"
      />
      <p class="m-0">{{ $t(asset_name) }}</p>
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
  margin: 10px 0px;
}
i {
  font-size: 32px;
}
</style>
