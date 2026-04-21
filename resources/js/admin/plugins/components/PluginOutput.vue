<script setup>
import { computed, ref, watch, onMounted, nextTick } from 'vue';

const commandOutput = ref('');
const status = ref('idle');
const exitCode = ref(0);
const pre = ref(null);
const props = defineProps({
  userId: {
    type: String,
    required: true,
  },
});

const isRunning = computed(() => {
  return ["started", "starting", "saving", "running"].includes(status.value);
});

const output = (text) => {
  if (typeof text !== "string") {
    return;
  }
  commandOutput.value += "\n" + text;
};
const scrollToBottom = () => {
  nextTick(() => {
    const node = pre.value;
    if (!node) {
      return;
    }
    setTimeout(() => {
      const el = pre.value;
      if (el) {
        el.scrollTop = el.scrollHeight;
      }
    }, 5);
  });
};

watch(commandOutput, () => {
  scrollToBottom();
});

onMounted(() => {
    window.Echo.private(`ProcessMaker.Models.User.${props.userId}`).listen(
      ".PluginLog",
      (event) => {
        const statusEvent = event.status;
        status.value = statusEvent;
        if (props.userId){
            switch (statusEvent) {
              case "starting":
                commandOutput.value = event.message;
                exitCode.value = 0;
                break;
              case "done":
                output(event.message);
                exitCode.value = 0;
                break;
              case "error":
                output(event.message);
                exitCode.value = 1;
                status.value = "done";
                break;
              default:
                output(event.message);
                break;
            }
        }
      }
    );
});
</script>

<style scoped>
.command-output {
  font-size: 0.7em;
  height: 200px;
}
.error {
  border-color: red !important;
}
</style>

<template>
  <div v-if="commandOutput !== '' || isRunning">
    <p>
      {{ $t("Plugin Installation Output") }}
      <i v-if="isRunning" class="fas fa-spinner fa-spin"></i>
    </p>
    <pre
      ref="pre"
      class="border command-output pre-scrollable"
      :class="{ error: exitCode !== 0 }">
        {{ commandOutput }}
    </pre>

    <div v-if="status === 'done'">
      <p v-if="exitCode === 0">
        {{
          $t("Plugin Successfully Installed. You can now close this window. ")
        }}
      </p>
      <div v-if="exitCode > 0" class="invalid-feedback d-block" role="alert">
        {{ $t("Error Installing Plugin. See Output Above.") }}
      </div>
    </div>
  </div>
</template>