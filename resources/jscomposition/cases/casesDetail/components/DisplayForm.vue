<template>
  <div class="tw-py-10 tw-px-32 tw-min-h-40 tw-max-h-[450px] tw-overflow-auto tw-relative">
    <button
      v-if="screen && screen.key !== 'default-form-screen'"
      type="button"
      class="tw-rounded-lg tw-bg-white tw-px-2 tw-py-1
        tw-float-end tw-absolute tw-right-4 tw-top-4
        tw-font-semibold tw-text-gray-500 tw-shadow-sm
        tw-ring-1 tw-ring-gray-300 hover:tw-bg-gray-50 tw-gap-2"
      @click="onPrint">
      <i class="fa fa-print tw-text-gray-600" />
    </button>

    <transition
      name="fade"
      mode="in-out">
      <div
        v-show="showPlaceholder"
        class="tw-flex tw-grow tw-w-full tw-h-full tw-pointer-events-none
          tw-absolute tw-left-0 tw-top-0 tw-z-10 tw-justify-center tw-items-center">
        <LoadingFormPlaceholder />
      </div>
    </transition>

    <transition
      name="fade"
      mode="in-out">
      <div
        v-show="!showPlaceholder && screen"
        class="tw-pointer-events-none">
        <vue-form-renderer
          v-if="screen !== null"
          v-model="previewData"
          :data="previewData"
          :config="screen.config"
          :custom-css="screen.custom_css"
          :show-errors="true" />
      </div>
    </transition>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import { getScreenData } from "../api/index";
import LoadingFormPlaceholder from "./placeholder/LoadingFormPlaceholder.vue";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const previewData = computed(() => props.data.taskData);
const screen = ref(null);
const showPlaceholder = ref(false);

const getScreen = async (screenId) => {
  showPlaceholder.value = true;

  const response = await getScreenData(screenId);

  setTimeout(() => {
    showPlaceholder.value = false;

    if (response.data) {
      screen.value = response.data;
    }
  }, 300);
};

const onPrint = () => {
  window.open(`/requests/${props.data.process_request_id}/task/${props.data.id}/screen/${screen.value.id}`);
};

onMounted(() => {
  getScreen(props.data?.id);
});
</script>
<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.9s ease;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}
</style>
