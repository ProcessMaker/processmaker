<template>
  <div>
    <!-- Modal -->
    <div class="modal fade" id="detailsTaskModal" tabindex="-1" role="dialog" aria-labelledby="detailsTaskModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div
            class="d-flex modal-header align-items-center py-2"
            style="background-color: #EFF5FF;"
          >
            <span> {{ $t("Details") }} </span>
            <button
              type="button"
              class="close"
              data-dismiss="modal"
              aria-label="Close"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" style="padding: 0 !important;">
            <table
              class="table b-table m-0"
              aria-label="fileDetails"
              role="table"
            >
              <!-- Need header for Sonar problems, it is not needed in the view-->
              <thead>
                <tr :class="statusCard">
                  <th class="d-flex align-items-center pl-3 border-0">
                    <h4 style="margin:0; padding:0; line-height:1">
                      {{ $t(task.advanceStatus) }}
                    </h4>
                  </th>
                  <th class="border-0">
                    <div v-if="dateDueAt && showDueAtDates">
                      <small> {{ $t(dueLabel) }} {{ moment(dateDueAt).fromNow() }}
                        <br>
                        {{ moment(dateDueAt).format() }}
                      </small>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td aria-colindex="1" role="cell" class="pl-3">
                    <span class="font-weight-normal"> {{ $t("Name of the Task") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    <span class="font-weight-light"> {{ task.element_name }}</span>
                  </td>
                </tr>
                <tr>
                  <td aria-colindex="1" role="cell" class="pl-3">
                    <span class="font-weight-normal"> {{ $t("Assigned") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell" class="font-weight-light">
                    {{ moment(createdAt).format() }}
                    <br>
                    <small class="font-weight-light"> {{ $t('Assigned') }} {{ moment(createdAt).fromNow() }}</small>
                  </td>
                </tr>
                <tr>
                  <td aria-colindex="1" role="cell" class="pl-3">
                    <span class="font-weight-normal"> {{ $t("Request") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    <span class="font-weight-light"> #{{ task.process_request_id }} {{ task.process_request.name }} </span>
                  </td>
                </tr>
                <tr>
                  <td aria-colindex="1" role="cell" class="pl-3">
                    <span class="font-weight-normal"> {{ $t("Requested By") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    <avatar-image
                      v-if="task.requestor"
                      size="25"
                      class="d-inline-flex pull-left align-items-center"
                      :input-data="task.requestor"
                      hide-name="true"
                    />
                    <p v-else>
                      {{ $t('Web Entry') }}
                    </p>
                  </td>
                </tr>
                <tr v-if="task.is_self_service === 0">
                  <td aria-colindex="1" role="cell" class="pl-3">
                    <span class="font-weight-normal"> {{ $t("Assigned To") }}: </span>
                  </td>
                  <td aria-colindex="2" role="cell">
                    <avatar-image
                      v-if="task.user"
                      size="25"
                      class="d-inline-flex pull-left align-items-center"
                      :input-data="task.user"
                      hide-name="true"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
            <div class="p-3">
              <button
                type="button"
                class="btn btn-outline-secondary btn-block"
                @click="eraseDraft()"
              >
                <img src="/img/smartinbox-images/eraser.svg" :alt="$t('No Image')">
                {{ $t('Clear Draft') }}
              </button>
            </div>
            <div v-if="task.definition.allowReassignment || userisadmin || userisprocessmanager" class="p-3">
              <button
                v-if="task.advanceStatus === 'open' || task.advanceStatus === 'overdue'"
                type="button"
                class="btn btn-outline-primary btn-block"
                data-dismiss="modal"
                data-toggle="modal"
                data-target="#reassignModal"
              >
                <i class="fas fa-user-friends" />
                {{ $t('Reassign') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <reassign-mobile-modal :task="task" />
  </div>
</template>

<script>
import AvatarImage from "../../components/AvatarImage.vue";
import ReassignMobileModal from "./ReassignMobileModal.vue";
import draftFileUploadMixin from "../../modules/autosave/draftFileUploadMixin";

Vue.component("AvatarImage", AvatarImage);
Vue.component("ReassignMobileModal", ReassignMobileModal);

Vue.mixin(draftFileUploadMixin);

export default {
  components: { ReassignMobileModal },
  props: ["task", "userisadmin", "userisprocessmanager"],
  data() {
    return {
      statusCard: "card-header text-capitalize text-white bg-success",
    };
  },
  computed: {
    dueLabel() {
      const dueLabels = {
        open: "Due",
        completed: "Completed",
        overdue: "Due",
      };
      return dueLabels[this.task.advanceStatus] || "";
    },
    dateDueAt() {
      return this.task.due_at;
    },
    showDueAtDates() {
      return this.task.status !== "CLOSED";
    },
    createdAt() {
      return this.task.created_at;
    },
    completedAt() {
      return this.task.completed_at;
    },
  },
  methods: {
    showRequestModal() {
      // Perform initial load of requests from backend
      this.$refs.requestModal.showModal();
    },
    eraseDraft() {
      this.formDataWatcherActive = false;
      ProcessMaker.apiClient
        .delete("drafts/" + this.task.id)
        .then(response => {
          this.resetRequestFiles(response);
          this.$emit("reload-task", true);
          this.closeModal();
        });
    },
    closeModal() {
      $('#detailsTaskModal').modal('hide');
    },
  },
};
</script>
