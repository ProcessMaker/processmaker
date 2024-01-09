<template>
  <div>
    <div class="d-flex justify-content-center my-5">
      <img
        class="empty-processes"
        src="/img/launchpad-images/empty-process.png"
        alt="Empty Process"
      >
    </div>
    <p class="text-center message-empty">
      {{ $t("No Processes Available") }}
    </p>
  </div>
</template>

<style scoped>
.empty-processes {
  width: 679px;
  height: 249px;
  flex-shrink: 0;
}
.message-empty {
  color: #000;
  font-size: 30px;
  font-style: normal;
  font-weight: 600;
  line-height: normal;
  letter-spacing: -0.6px;
}
.empty-container {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
