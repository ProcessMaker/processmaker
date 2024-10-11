<template>
  <div class="tw-flex tw-grow tw-relative">
    <transition
      name="fade"
      mode="in-out">
      <div
        v-show="showPlaceholder"
        class="tw-flex tw-grow tw-w-full tw-h-full tw-pointer-events-none
          tw-absolute tw-left-0 tw-top-0 tw-z-10 tw-justify-center tw-items-center">
        <LoadingPlaceholder />
      </div>
    </transition>

    <transition
      name="fade"
      mode="in-out">
      <object
        v-if="!showPlaceholder"
        ref="processMap"
        class="card"
        :data="url"
        width="100%"
        :height="'auto'"
        frameborder="0"
        type="text/html"
        style="border-radius: 4px;"
        @load="onLoadedObject">
        <!-- Accessible Alternative Content -->
        {{ $t('Content not available. Check settings or try a different device.') }}
      </object>
    </transition>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { getRequest } from "../variables";
import LoadingPlaceholder from "./placeholder/LoadingPlaceholder.vue";

const { id, process_id } = getRequest();
const url = ref();
const showPlaceholder = ref(false);

const onLoadedObject = () => {
  setTimeout(() => {
    showPlaceholder.value = false;
  }, 500);
};

onMounted(() => {
  showPlaceholder.value = true;
  url.value = `${window.document.location.origin}/modeler/${process_id}/inflight/${id}`;
});
</script>
