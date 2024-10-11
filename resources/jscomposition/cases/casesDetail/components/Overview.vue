<template>
  <div class="tw-flex tw-grow">
    <object
      v-if="url"
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
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { getRequest } from "../variables";

const { id, process_id } = getRequest();
const url = ref();

const onLoadedObject = () => {

};

onMounted(() => {
  url.value = `${window.document.location.origin}/modeler/${process_id}/inflight/${id}`;
});
</script>
