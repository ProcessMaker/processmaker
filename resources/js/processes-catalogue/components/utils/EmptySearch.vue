<template>
  <div>
    <div class="d-flex justify-content-center my-5">
      <i class="empty-processes fa fa-umbrella-beach" />
    </div>
    <p class="text-center message-empty">
      {{ $t("No Processes Available") }}
    </p>
  </div>
</template>

<style scoped>
.empty-processes {
  font-size: 200px;
}
.message-empty {
  color: #000;
  font-size: 30px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
  letter-spacing: -0.6px;
}
</style>
