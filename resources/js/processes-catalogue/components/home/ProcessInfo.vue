<template>
  <slide-process-info
    :show="showProcessInfo"
    :title="title"
    :process="process"
    :full-carousel="fullCarousel"
    @closeCarousel="closeFullCarousel"
    @close="closeProcessInfo">
    <div class="tw-flex tw-flex-col tw-gap-4 tw-pl-10 tw-pr-10">
      <carousel-slide
        :process="process"
        @full-carousel="showFullCarousel" />
      <div v-show="!fullCarousel">
        <process-options
          class="tw-w-full"
          :process="process"
          :collapsed="collapsed" />
        <progress-bar-section :stages-summary="process.stagesSummary" />
      </div>
    </div>
  </slide-process-info>
</template>

<script setup>
import { ref, computed } from "vue";
import { t } from "../variables/index";
import SlideProcessInfo from "../slideProcessInfo/SlideProcessInfo.vue";
import CarouselSlide from "../CarouselSlide.vue";
import ProcessOptions from "../ProcessOptions.vue";
import ProgressBarSection from "../progressBar/ProgressBarSection.vue";

const props = defineProps({
  showProcessInfo: {
    type: Boolean,
    required: true,
  },
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["update:showProcessInfo"]);
const showProcessInfo = computed({
  get() {
    return props.showProcessInfo;
  },
  set(value) {
    emit("update:showProcessInfo", value);
  },
});
const title = computed(() => (props.fullCarousel
  ? props.process.name
  : t("Process Information")));

const fullCarousel = ref(false);

const closeFullCarousel = () => {
  fullCarousel.value = false;
};

const closeProcessInfo = () => {
  showProcessInfo.value = false;
};

const showFullCarousel = () => {
  fullCarousel.value = true;
};

const collapsed = ref(true);
</script>
