<template>
  <div
    id="reassign-container"
    class="d-flex align-items-center overlay-div position-absolute top-0 start-0 w-100 bg-white shadow-lg p-2 pr-4">
    <div class="mr-3">
      <label for="user">Assign to:</label>
    </div>
    <div class="flex-grow-1">
      <PMDropdownSuggest
        v-model="selectedUser"
        :options="reassignUsers"
        :placeholder="$t('Type here to search')"
        @onInput="onReassignInput">
        <template #pre-text="{ option }">
          <b-badge
            variant="secondary"
            class="mr-2 custom-badges pl-2 pr-2 rounded-lg">
            {{ option.active_tasks_count }}
          </b-badge>
        </template>
      </PMDropdownSuggest>
    </div>
    <button
      type="button"
      class="btn btn-primary btn-sm ml-2"
      :disabled="disabled"
      @click="reassignUser">
      {{ $t('Assign') }}
    </button>
    <button
      type="button"
      class="btn btn-outline-secondary btn-sm ml-2"
      @click="cancelReassign">
      {{ $t('Cancel') }}
    </button>
  </div>
</template>

<script setup>
import {
  ref, onMounted, nextTick, computed,
} from "vue";
import PMDropdownSuggest from "../../../components/PMDropdownSuggest.vue";
import { getReassignUsers, updateReassignUser } from "../../api";

const { _ } = window;

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["on-reassign-user"]);

const selectedUser = ref(null);
const reassignUsers = ref([]);

const disabled = computed(() => !selectedUser.value);

const currentTaskUserId = computed(() => props.task.user_id);

// Load the reassign users
const loadReassignUsers = async () => {
  const response = await getReassignUsers();

  reassignUsers.value = [];
  response.data.forEach((user) => {
    if (currentTaskUserId.value === user.id) {
      return;
    }
    reassignUsers.value.push({
      text: user.fullname,
      value: user.id,
      active_tasks_count: user.active_tasks_count,
    });
  });
};

const onReassignInput = _.debounce(async (filter) => {
  await loadReassignUsers(filter);
}, 300);

const reassignUser = async () => {
  if (selectedUser.value) {
    const response = await updateReassignUser(props.task.id, selectedUser.value);
    console.log("REASSIGN USER RESPONSE", response);
    if (response) {
      emit("on-reassign-user", selectedUser.value);
    }

    nextTick(() => {
      selectedUser.value = null;
    });
  }
};
const cancelReassign = () => {
  selectedUser.value = null;
};

onMounted(async () => {
  await loadReassignUsers();
});
</script>
