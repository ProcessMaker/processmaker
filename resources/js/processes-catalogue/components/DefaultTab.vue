<template>
  <div class="d-flex align-items-center justify-content-center flex-column tab-container">
    <div class="content-text">
      <img
        :src="imageSrc"
        :alt="altText"
      />
      <span class="title">{{ titleText }}</span>
      <span class="title-section">{{ descriptionText }}</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    imageSrc: {
      type: String,
      default: "/img/cases-loading.svg",
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
.content-text .title,
.content-text .title-section {
  line-height: 1.2;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
.title {
  color: var(--text-only, #556271);
  text-align: center;
  font-family: "Open Sans", sans-serif;
  font-size: 24px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px; /* 158.333% */
  letter-spacing: -0.96px;
}
.title-section {
  color: var(--text-only, #556271);
  text-align: center;
  font-family: "Open Sans", sans-serif;
  font-size: 15px;
  font-style: normal;
  font-weight: 400;
  line-height: 27px; /* 180% */
  letter-spacing: -0.3px;
}
</style>
