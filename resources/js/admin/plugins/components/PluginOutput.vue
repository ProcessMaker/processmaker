<script setup>
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';

const commandOutput = ref('');
const status = ref('idle');
const exitCode = ref(0);
const pre = ref(null);
const props = defineProps({
  userId: {
    type: String,
    required: true,
  },
  outputTitle: {
    type: String,
    default: "",
  },
  successMessage: {
    type: String,
    default: "",
  },
  errorMessage: {
    type: String,
    default: "",
  },
});
const emit = defineEmits(['done', 'error', 'running']);

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

const channelName = computed(
  () => `ProcessMaker.Models.User.${props.userId}`
);

let pluginLogChannel = null;

const onPluginLog = (event) => {
  const statusEvent = event.status;
  status.value = statusEvent;
  if (!props.userId) {
    return;
  }
  switch (statusEvent) {
    case "starting":
      commandOutput.value = event.message;
      exitCode.value = 0;
      emit("running", event);
      break;
    case "done":
      output(event.message);
      exitCode.value = 0;
      emit("done", event);
      break;
    case "error":
      output(event.message);
      exitCode.value = 1;
      status.value = "done";
      emit("error", event);
      break;
    default:
      output(event.message);
      emit("running", event);
      break;
  }
};

onMounted(() => {
  if (!props.userId || !window.Echo) {
    return;
  }
  pluginLogChannel = window.Echo.private(channelName.value);
  pluginLogChannel.listen(".PluginLog", onPluginLog);
});

onUnmounted(() => {
  if (pluginLogChannel) {
    pluginLogChannel.stopListening(".PluginLog", onPluginLog);
    pluginLogChannel = null;
  }
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
      {{ outputTitle || $t("Plugin Installation Output") }}
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
          successMessage ||
            $t("Plugin Successfully Installed. You can now close this window. ")
        }}
      </p>
      <div v-if="exitCode > 0" class="invalid-feedback d-block" role="alert">
        {{
          errorMessage || $t("Error Installing Plugin. See Output Above.")
        }}
      </div>
    </div>
  </div>
</template>