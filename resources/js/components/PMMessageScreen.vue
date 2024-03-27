<template>
  <div class="d-flex justify-content-center align-items-center flex-column pm-message-screen">
    <slot name="content">
    </slot>
  </div>
</template>

<style scoped>
  .pm-message-screen {
  }
  .pm-message-screen > :first-child {
    margin-top: 2rem !important;
  }
  .pm-message-screen > * {
    text-align: center;
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
  }
  .pm-message-screen > :not(img):not(svg) {
    width: 520px;
  }
  .pm-message-screen b {
    font-size: 24px;
  }
</style>