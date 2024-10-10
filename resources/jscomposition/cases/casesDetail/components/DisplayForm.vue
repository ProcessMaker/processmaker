<template>
  <div class="tw-py-10 tw-px-32 tw-max-h-[450px] tw-overflow-auto">
    <vue-form-renderer
      v-if="screen !== null"
      v-model="previewData"
      :config="screen.config"
      :computed="screen.computed"
      :custom-css="screen.custom_css"
      :watchers="screen.watchers"
      :show-errors="true"
    />
  </div>
</template>

<script>
import { defineComponent, onMounted, ref } from "vue";
import { getScreenData } from "../api/index";

export default defineComponent({
  props: {
    data: {
      type: Object,
      required: true,
    },
  },
  setup(props) {
    const previewData = ref({});
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
