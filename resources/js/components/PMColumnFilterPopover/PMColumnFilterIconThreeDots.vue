<template>
  <span>
    <svg v-if="(!columnSortAsc && !columnSortDesc && !filterApplied) || columnMouseover" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
      <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
    </svg>
    <PMColumnFilterIconAsc v-if="columnSortAsc && !columnMouseover"></PMColumnFilterIconAsc>
    <PMColumnFilterIconDesc v-if="columnSortDesc && !columnMouseover"></PMColumnFilterIconDesc>
    <i v-if="filterApplied && !columnMouseover" class="fas fa-filter"></i>
  </span>
</template>
<script>

import PMColumnFilterIconAsc from './PMColumnFilterIconAsc.vue';
import PMColumnFilterIconDesc from './PMColumnFilterIconDesc.vue';

export default {
  components: {
    PMColumnFilterIconAsc,
    PMColumnFilterIconDesc
  },
  props: [
    "columnSortAsc",
    "columnSortDesc",
    "filterApplied",
    "columnMouseover",
  ],
};
</script>
