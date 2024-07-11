<template>
  <b-card-text class="asset">
    <span>
      <img
        v-if="image_icon"
        :src="image_icon"
        :alt="asset_name"
      >
      <i
        v-if="icon"
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
    "icon",
    "image_icon",
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
  margin: 12px 0px;
}
i {
  font-size: 32px;
}
p {
  font-size: 16px;
  font-weight: 400;
}
</style>
