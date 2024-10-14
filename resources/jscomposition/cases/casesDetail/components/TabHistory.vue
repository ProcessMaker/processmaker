<template>
  <div class="tw-flex tw-flex-col tw-gap-2">
    <span>{{ processName }}</span>
    <TimelineCases
      :commentable_id="commentableId"
      :commentable_type="comentableType"
      :adding="false"
      :readonly="status === 'COMPLETED'"
      :timeline="false" />
  </div>
</template>

<script setup>
import { ref } from "vue";
import TimelineCases from "./TimelineCases.vue";
import {
  getRequestStatus, getComentableType, getProcessName, getCaseNumber,
} from "../variables";

const status = ref(getRequestStatus());
const commentableId = ref(getCaseNumber());
const comentableType = ref(getComentableType());
const processName = ref(getProcessName());

</script>
