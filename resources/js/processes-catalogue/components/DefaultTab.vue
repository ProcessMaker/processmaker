<template>
  <div class="d-flex align-items-center justify-content-center flex-column tab-container">
    <img
      :src="imageSrc"
      :alt="altText"
    />
    <div class="text-center">
      <strong>{{ titleText }}</strong>
    </div>
    <div class="text-center">
      {{ descriptionText }}
    </div>
  </div>
</template>

<script>
export default {
  props: {
    imageSrc: {
      type: String,
      default: "/img/tasks-loading.svg",
    },
    altText: {
      type: String,
      default: "",
    },
    titleText: {
      type: String,
      default: "",
    },
    descriptionText: {
      type: String,
      default: "",
    },
  },
};
</script>

<style scoped>
.tab-container {
    width: auto;
    height: 622px;
    background-color: white;
  }
</style>
