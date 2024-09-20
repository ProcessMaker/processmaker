<template>
  <div class="tw-flex tw-flex-col tw-gap-2">
    <span>{{ processName }}</span>
    <TimeLine
      :commentable_id="commentableId"
      :commentable_type="comentableType"
      :adding="false"
      :readonly="status === 'COMPLETED'"
      :timeline="false"
    />
  </div>
</template>

<script>
import { defineComponent, ref } from "vue";
import TimeLine from "../../../../js/components/Timeline.vue";
import {
  getRequestId, getRequestStatus, getComentableType, getProcessName,
} from "../api/data";

export default defineComponent({
  components: { TimeLine },
  setup() {
    const status = ref(getRequestStatus());
    const commentableId = ref(getRequestId());
    const comentableType = ref(getComentableType());
    const processName = ref(getProcessName());
    return {
      status,
      commentableId,
      comentableType,
      processName,
    };
  },
});
</script>
