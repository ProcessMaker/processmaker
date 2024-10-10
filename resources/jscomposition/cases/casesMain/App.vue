<template>
  <div class="tw-flex tw-h-[90vh] ">
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
