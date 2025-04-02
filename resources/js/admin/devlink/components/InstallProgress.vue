<template>
  <div>
    <div class="install-progress">
      <b-spinner v-if="showSpinner"></b-spinner>
      <b-progress
        :value="progress"
        :max="100"
        show-progress
        animated
      ></b-progress>
      <div class="current-message">{{ currentMessage }}</div>
      <div class="warnings" v-if="warnings.length > 0">
        <ul>
          <li
            v-for="(warning, index) in warnings"
            :key="index"
          >
            {{ warning }}
          </li>
        </ul>
      </div>
    </div>
    <div v-if="error">
      {{ $t('Something went wrong') }}
      <pre class="error-message">{{ error }}</pre>
    </div>
    <div v-if="done">
      <b-button @click="handleClose">
        {{ $t('Close') }}
      </b-button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, getCurrentInstance } from 'vue';

const vue = getCurrentInstance().proxy;
const progress = ref(0);
const userId = window.ProcessMaker.user.id;
const done = ref(false);
const currentMessage = ref('');
const warnings = ref([]);
const showSpinner = ref(true);
const error = ref('');
const emit = defineEmits(['installation-complete']);

onMounted(() => {
  currentMessage.value = vue.$t('Initializing') + '...';
  window.Echo.private(`ProcessMaker.Models.User.${userId}`).listen(
    '.ImportLog',
    (response) => {
      showSpinner.value = false;
      if (response.type === 'progress') {
        progress.value = response.message;
      }

      if (response.type === 'status') {
        if (response.message === 'done') {
          progress.value = 100;
          warnings.value = response.additionalParams;
          done.value = true;
          currentMessage.value = 'Successfully installed!';
        } else {
          currentMessage.value = response.message;
        }
      }

      if (response.type === 'log') {
        currentMessage.value = response.message;
      }

      if (response.type === 'error') {
        error.value = response.message;
        done.value = true;
      }
    });
});

onUnmounted(() => {
  window.Echo.leave(`ProcessMaker.Models.User.${userId}`);
});

const handleClose = () => {
  vue.$bvModal.hide('install-progress');
  emit('installation-complete');
};
</script>

<style scoped>
.install-progress {
  padding: 20px;
}
.current-message {
  word-wrap: break-word;
  height: 40px;
  overflow: hidden;
}
.warnings {
  margin-top: 20px;
  color: red;
}
</style>
