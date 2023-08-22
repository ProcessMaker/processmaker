<template>
  <div class="container">
    <div class="content">
      <img
        class="image"
        src="/img/tasks-loading.svg"
      >
      <div class="content-text">
        <h1>{{ $t("The task is loading") }}</h1>
        <span>{{ $t("Some assets can take some time to load") }}</span>
        <b-spinner variant="primary" />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
};
</script>

<style scoped>
.image {
  width: 178px;
  height: 99px;
}
.container {
  display: flex;
  padding: 140px 151.5px;
  justify-content: center;
  align-items: center;
  flex: 1 0 0;
  align-self: stretch;
}
.content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 40px;
}
.content-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}
h1 {
  color: var(--secondary-800, #44494E);
  /* Heading 2/Semi-Bold */
  font-family: Open Sans;
  font-size: 32px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px; /* 118.75% */
  letter-spacing: -1.28px;
}
span {
  color: var(--secondary-800, #44494E);
  text-align: center;
  /* Paragraph 3/Regular */
  font-family: Open Sans;
  font-size: 18px;
  font-style: normal;
  font-weight: 400;
  line-height: 27px; /* 150% */
  letter-spacing: -0.36px;
}
</style>
