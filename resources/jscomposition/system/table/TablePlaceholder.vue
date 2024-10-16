<template>
  <component
    :is="getComponent"
    v-bind="getOptions()" />
</template>

<script setup>
import { t } from "i18next";
import { computed } from "vue";
import { LoadingPlaceholder, EmptyPlaceholder } from "../placeholders";

const props = defineProps({
  placeholder: {
    type: String,
    default: () => "loading",
  },
});

const placeholders = {
  loading: {
    component: LoadingPlaceholder,
    options: {},
  },
  "empty-cases": {
    component: EmptyPlaceholder,
    options: {
      title: t("All clear"),
      subtitle: t("No new cases at this moment"),
    },
  },
  "empty-tasks": {
    component: EmptyPlaceholder,
    options: {
      title: t("All clear"),
      subtitle: t("No new tasks at this moment"),
    },
  },
};

const getComponent = computed(() => placeholders[props.placeholder].component);
const getOptions = () => placeholders[props.placeholder].options;
</script>
