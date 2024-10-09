<template>
  <div class="tw-py-10 tw-px-32 tw-max-h-[450px] tw-overflow-auto">
    <div class="tw-pointer-events-none">
      <vue-form-renderer
        v-if="screen !== null"
        v-model="previewData"
        :data="previewData"
        :config="screen.config"
        :custom-css="screen.custom_css"
        :show-errors="true"
      />
    </div>
  </div>
</template>

<script>
import { defineComponent, onMounted, ref, computed } from "vue";
import { getScreenData } from "../api/index";

export default defineComponent({
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const previewData = computed(() => props.data.taskData);
    const screen = ref(null);

    const getScreen = (screenId) => {
      Promise.resolve(getScreenData(screenId)).then((response) => { screen.value = response.data; });
    };

    onMounted(() => {
      getScreen(props.data?.screen_id);
    });

    return {
      screen,
      previewData,
    };
  },
});
</script>
