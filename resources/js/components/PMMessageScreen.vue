<template>
  <div class="d-flex justify-content-center align-items-center flex-column pm-message-screen">
    <img src="/img/inbox-rule-suggest-lg.svg" :alt="$t('Inbox rules empty')" />
    <b class="mt-3 mb-3 pm-message-screen-b">{{ $t("You haven't set up any Inbox Rules yet") }}</b>
    <span class="text-center pm-message-screen-span" 
          v-html="$t('Inbox Rules act as your personal task manager. You tell them what to look for, and <b>they take care of things automatically</b>.')">
    </span>
    <a href="#" 
       class="mt-4">
      {{ $t("Create an Inbox Rule Now") }}
    </a>
  </div>
</template>

<style scoped>
  .pm-message-screen {
  }
  .pm-message-screen-b {
    font-size: 24px;
  }
  .pm-message-screen-span {
    width: 520px;
  }
</style>