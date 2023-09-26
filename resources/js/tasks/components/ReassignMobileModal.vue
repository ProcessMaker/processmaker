<template>
  <!-- Modal -->
  <div
    id="reassignModal"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    size="md"
    aria-labelledby="reassignModalLabel"
    aria-hidden="true"
  >
    <div
      class="modal-dialog modal-dialog-centered"
      role="document"
    >
      <div class="modal-content">
        <!-- Header -->
        <div class="modal-header">
          <h5
            id="exampleModalLabel"
            class="modal-title"
          >
            {{ $t('Reassign To') }}
          </h5>
          <button
            type="button"
            class="close"
            data-dismiss="modal"
            aria-label="Close"
            @click="cancelReassign"
          >
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <!-- Body -->
        <div class="modal-body">
          <div class="form-group">
            <select-from-api
              id="user"
              v-model="selectedUser"
              :placeholder="$t('Select the user to reassign to the task')"
              api="users"
              :multiple="false"
              :show-labels="false"
              :searchable="true"
              :store-id="false"
              label="fullname"
            >
              <template slot="noResult">
                {{ $t('No elements found. Consider changing the search query.') }}
              </template>
              <template slot="noOptions">
                {{ $t('No Data Available') }}
              </template>
              <template slot="tag" slot-scope="props">
                <span
                  class="multiselect__tag  d-flex align-items-center"
                  style="width:max-content;"
                >
                  <span class="option__desc mr-1">
                    <span class="option__title">
                      {{ props.option.fullname }}
                    </span>
                  </span>
                  <i
                    aria-hidden="true"
                    class="multiselect__tag-icon"
                    tabindex="1"
                    @click="props.remove(props.option)"
                  />
                </span>
              </template>
              <template
                slot="option"
                slot-scope="props"
              >
                <div class="option__desc d-flex align-items-center">
                  <span class="option__title mr-1">
                    {{ props.option.fullname }}
                  </span>
                </div>
              </template>
            </select-from-api>
          </div>
        </div>
        <!-- Footer -->
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-outline-secondary"
            data-dismiss="modal"
            @click="cancelReassign"
          >
            {{ $t('Cancel') }}
          </button>
          <button
            type="button"
            class="btn btn-primary ml-2"
            :disabled="disabled"
            @click="reassignUser"
          >
            {{ $t('Reassign') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AvatarImage from "../../components/AvatarImage.vue";

Vue.component("AvatarImage", AvatarImage);

export default {
  props: ["task"],
  data() {
    return {
      selectedUser: [],
    };
  },
  computed: {
    disabled() {
      return this.selectedUser.length === 0;
    },
  },
  methods: {
    cancelReassign() {
      this.selectedUser = [];
    },
    reassignUser() {
      if (this.selectedUser) {
        ProcessMaker.apiClient
          .put(`tasks/${this.task.id}`, {
            user_id: this.selectedUser.id,
          })
          .then((response) => {
            this.selectedUser = [];
            window.location.href = "/tasks";
          });
      }
    },
  },
};
</script>
<style>
  .inline-input {
    margin-right: 6px;
  }

  .inline-button {
    background-color: rgb(109, 124, 136);
    font-weight: 100;
  }

  .input-and-select {
    width: 212px;
  }
  .multiselect__element span img {
    border-radius: 50%;
    height: 20px;
  }

  .multiselect__tags-wrap img {
    height: 15px;
    border-radius: 50%;
  }

  .multiselect__tag-icon:after {
    color: white;
  }

  .multiselect__option--highlight {
    background: #00bf9c;
  }

  .multiselect__option--selected.multiselect__option--highlight {
    background: #00bf9c;
  }

  .multiselect__tag {
    background: #788793;
  }

  .multiselect__tag-icon:after {
    color: white;
  }
</style>
