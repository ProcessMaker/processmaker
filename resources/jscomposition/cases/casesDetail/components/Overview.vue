<template>
  <div class="tw-flex tw-grow tw-relative">
    <transition
      name="fade"
      mode="in-out">
      <object
        ref="processMap"
        class="card"
        :data="url"
        width="100%"
        :height="'auto'"
        frameborder="0"
        type="text/html"
        style="border-radius: 4px;">
        <!-- Accessible Alternative Content -->
        {{ $t('Content not available. Check settings or try a different device.') }}
      </object>
    </transition>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { getRequest } from "../variables";

// Important: onLoadedObject is not working in firefox
const { id, process_id } = getRequest();
const url = ref();

onMounted(() => {
  url.value = `${window.document.location.origin}/modeler/${process_id}/inflight/${id}`;
});
</script>
