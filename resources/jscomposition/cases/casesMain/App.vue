<template>
  <div class="tw-p-4">
    <RouterView></RouterView>
  </div>
</template>
<script>
import { defineComponent } from "vue";
import { RouterView } from "vue-router";

export default defineComponent({
  components: {
    RouterView,
  },
  setup() {
    return {};
  },
});
</script>
