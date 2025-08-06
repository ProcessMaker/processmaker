<template>
  <div
    id="reassign-container"
    class="tw-flex tw-flex-col tw-space-y-3 tw-items-center overlay-div tw-absolute top-0 start-0 tw-w-full bg-white shadow-lg tw-p-2"
  >
    <div class="tw-flex tw-flex-col tw-space-x-2 tw-p-2 tw-w-full">
      <label>{{ $t('Assign to') }}:</label>
      <PMDropdownSuggest
        v-model="selectedUser"
        :options="reassignUsers"
        :placeholder="$t('Type here to search')"
        @onInput="onReassignInput"
      >
        <template #pre-text="{ option }">
          <b-badge
            variant="secondary"
            class="mr-2 custom-badges pl-2 pr-2 rounded-lg"
          >
            {{ option.active_tasks_count }}
          </b-badge>
        </template>
      </PMDropdownSuggest>
    </div>

    <div class="tw-flex tw-flex-col tw-space-x-2 tw-p-2 tw-w-full">
      <label>{{ $t('Comments') }}</label>
      <textarea
        v-model="comments"
        rows="5"
        class="tw-w-full tw-border tw-border-gray-300 tw-rounded-md
          tw-resize-none placeholder:tw-text-gray-400/80 placeholder:tw-text-sm tw-p-2"
        :placeholder="$t('Add a comment to the assignment')"
      />
    </div>

    <div class="tw-flex tw-flex-row tw-space-x-2 tw-w-full tw-justify-end">
      <button
        type="button"
        class="btn btn-outline-secondary btn-sm ml-2"
        @click="cancelReassign"
      >
        {{ $t('Cancel') }}
      </button>
      <button
        type="button"
        class="btn btn-primary btn-sm ml-2"
        :disabled="disabled || disabledAssign"
        @click="reassignUser"
      >
        {{ $t('Assign') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import {
  ref, onMounted, nextTick, computed,
} from "vue";
import PMDropdownSuggest from "../../../components/PMDropdownSuggest.vue";
import { getReassignUsers, updateReassignUser, updateComment } from "../../api";

const { _ } = window;

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["on-reassign-user"]);

// Refs
const selectedUser = ref(null);
const comments = ref("");
const reassignUsers = ref([]);
const disabledAssign = ref(false);

// Computed properties
const disabled = computed(() => !selectedUser.value || !comments.value?.trim());

// Load the reassign users
const loadReassignUsers = async (filter) => {
  const response = await getReassignUsers(filter, props.task.id);

  reassignUsers.value = [];
  response.data.forEach((user) => {
    reassignUsers.value.push({
      text: user.fullname,
      value: user.id,
      active_tasks_count: user.active_tasks_count,
    });
  });
};

/**
 * Load the reassign users
 */
const onReassignInput = _.debounce(async (filter) => {
  await loadReassignUsers(filter);
}, 300);

/**
 * Prepare the comment (data and parameters) to be updated
 */
const prepareToUpdateComment = async () => {
  const comment = {
    body: comments.value,
    commentableId: props.task.id,
    commentableType: "ProcessMaker\\Models\\ProcessRequestToken",
    type: "COMMENT",
    parent_id: 0,
    subject: "Commented on Reassigned Task",
  };

  const response = await updateComment(comment);

  return response;
};

/**
 * Reassign the user
 */
const reassignUser = async () => {
  disabledAssign.value = true;
  if (selectedUser.value) {
    try {
      // First create the comment only if comments.value is not empty
      comments.value && comments.value.trim() && await prepareToUpdateComment();

      // Then reassign the task
      const response = await updateReassignUser(props.task.id, selectedUser.value, comments.value);

      // Emit success even if comment creation fails (comments package might not be active)
      response && emit("on-reassign-user", selectedUser.value);

      nextTick(() => {
        selectedUser.value = null;
      });
    } catch (error) {
      console.error(error);
    } finally {
      disabledAssign.value = false;
    }
  }
};

/**
 * Cancel the reassign
 */
const cancelReassign = () => {
  selectedUser.value = null;
  emit("on-cancel-reassign");
};

onMounted(async () => {
  await loadReassignUsers();
});
</script>
