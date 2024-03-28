<template>
  <div class="empty">
    <div class="d-flex justify-content-center my-5">
      <img
        class="image d-flex"
        src="/img/no-results.svg"
        :alt="$t('No results to show')"
      >
    </div>
    <div class="message">
      <h4 class="text-center title">
        {{ $t("No results to show") }}
      </h4>
      <p class="text-center content">
        {{ $t("Sorry, we couldn't find any results for your search. Check your spelling or use other terms.") }}
      </p>
    </div>
  </div>
</template>

<style scoped>
.empty {
  height: calc(100vh - 290px);
  align-content: center;
}
.image {
  width: 114.401px;
  height: 118px;
}
.message {
  display: inline-block;
  width: 358px;
}
.title {
  color: var(--text-only, #556271);
  font-size: 24px;
  font-style: normal;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: -0.96px;
}
.content {
  color: var(--text-only, #556271);
  line-height: 27px;
  letter-spacing: -0.32px;
}
</style>
