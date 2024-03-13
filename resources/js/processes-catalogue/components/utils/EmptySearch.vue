<template>
  <div class="py-5">
    <div class="d-flex justify-content-center my-5">
      <img
          class="image d-flex empty-processes"
          src="/img/launchpad-images/emptyResults.svg"
          alt="Empty Results"
        >
    </div>
    <p class="text-center title-empty">
      {{ $t("No Processes Available") }}
    </p>
    <p class="text-center message-empty">
      {{ $t("Sorry, we couldn't find any results for your search.") }}
    </p>
    <p class="text-center message-empty">
      {{ $t("Check your spelling or use other terms.") }}
    </p>
  </div>
</template>

<style scoped>
.empty-processes {
  height: 118px;
}
.title-empty {
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 24px;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -0.04em;
}
.message-empty {
  color: #556271;
  margin-bottom: 0px;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 27px;
  letter-spacing: -0.02em;
}
</style>
