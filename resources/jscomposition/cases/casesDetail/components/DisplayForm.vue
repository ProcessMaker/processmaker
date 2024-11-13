<template>
  <div class="tw-py-10 tw-px-32 tw-min-h-40 tw-max-h-[450px] tw-overflow-auto tw-relative">
    <button
      v-if="screen && screen.key !== 'default-form-screen'"
      type="button"
      class="tw-rounded-lg tw-bg-white tw-px-2 tw-py-1
        tw-float-end tw-absolute tw-right-4 tw-top-4
        tw-font-semibold tw-text-gray-500 tw-shadow-sm
        tw-ring-1 tw-ring-gray-300 hover:tw-bg-gray-50 tw-gap-2"
      @click="onPrint"
    >
      <i class="fa fa-print tw-text-gray-600" />
    </button>

    <transition
      name="fade"
      mode="in-out"
    >
      <div
        v-show="showPlaceholder"
        class="tw-flex tw-grow tw-w-full tw-h-full tw-pointer-events-none
          tw-absolute tw-left-0 tw-top-0 tw-z-10 tw-justify-center tw-items-center"
      >
        <LoadingPlaceholder />
      </div>
    </transition>

    <transition
      name="fade"
      mode="in-out"
    >
      <div
        v-show="!showPlaceholder && screen"
        class="tw-pointer-events-none"
      >
        <div
          v-for="(page, index) in pagesPrintable"
          :key="index"
          class="card"
        >
          <div class="card-body">
            <vue-form-renderer
              v-if="screen !== null"
              ref="formRender"
              v-model="previewData"
              :data="previewData"
              :config="configScreen"
              :custom-css="screen.custom_css"
              :show-errors="true"
            />
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import {
  onMounted, ref, computed, nextTick,
} from "vue";
import { getScreenData } from "../api/index";
import LoadingPlaceholder from "./placeholder/LoadingPlaceholder.vue";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const previewData = computed(() => props.data.taskData);
const screen = ref(null);
const configScreen = ref({});
const showPlaceholder = ref(false);
const pagesPrintable = ref([]);
const formRender = ref([]);

const findPagesInNavButtons = (object, found = []) => {
  if (object.items) {
    object.items.forEach((item) => {
      findPagesInNavButtons(item, found);
    });
  } else if (object instanceof Array) {
    object.forEach((item) => {
      findPagesInNavButtons(item, found);
    });
  } else if (object.config && object.config.event === "pageNavigate" && object.config.eventData) {
    const page = parseInt(object.config.eventData, 10);
    if (found.indexOf(page) === -1) {
      found.push(page);
    }
  }
};

const loadPages = () => {
  const pages = [0];
  if (screen.value.config instanceof Array) {
    screen.value.config.forEach((page) => {
      findPagesInNavButtons(page, pages);
    });
  }
  return pages;
};

const disableForm = (screenConfig) => {
  if (screenConfig instanceof Array) {
    for (let i = screenConfig.length - 1; i >= 0; i -= 1) {
      if (
        screenConfig[i].component === "FormButton"
        || screenConfig[i].component === "FileUpload"
        || screenConfig[i].component === "PhotoVideo"
      ) {
        screenConfig.splice(i, 1);
      } else {
        disableForm(screenConfig[i]);
      }
    }
  }
  if (screenConfig.config !== undefined) {
    screenConfig.config.disabled = true;
    screenConfig.config.readonly = true;
    screenConfig.config.editable = false;
  }
  if (screenConfig.items !== undefined) {
    disableForm(screenConfig.items);
  }
  return screenConfig;
};

const loadPagesPrint = () => {
  nextTick(() => {
    formRender.value.forEach((page, index) => {
      if (page.setCurrentPage) {
        page.setCurrentPage(pagesPrintable.value[index]);
      }
    });
  });
};

const getScreen = async (screenId) => {
  showPlaceholder.value = true;

  const response = await getScreenData(screenId);

  setTimeout(() => {
    showPlaceholder.value = false;

    if (response.data) {
      screen.value = response.data;
      pagesPrintable.value = loadPages();
      configScreen.value = disableForm(screen.value.config);
      loadPagesPrint();
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
