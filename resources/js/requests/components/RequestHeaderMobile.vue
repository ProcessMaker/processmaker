<template>
  <div v-if="statusLabel" class="card border-0">
    <table
      class="table b-table m-0 border-0"
      aria-label="fileDetails"
      role="table"
    >
      <!-- Need header for Sonar problems, it is not needed in the view-->
      <thead :class="classStatusCard">
        <tr>
          <th class="d-flex align-items-center pl-3 border-0">
            <h4 style="margin:0; padding:0; line-height:1">
              {{ $t(statusLabel) }}
            </h4>
          </th>
          <th class="border-0">
            <div>
              <p class="small mb-1">
                {{ $t('Start') }}:
                {{ moment(startDate).format() }}
              </p>
              <p class="small m-0">
                {{ $t(statusDateLabel) }}:
                {{ statusDate }}
              </p>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="request.user_id">
          <td aria-colindex="1" role="cell" class="pl-3">
            <span class="font-weight-normal"> {{ $t("Requested By") }}: </span>
          </td>
          <td aria-colindex="2" role="cell">
            <avatar-image
              v-if="userRequested"
              size="25"
              class="d-inline-flex pull-left align-items-center"
              :input-data="requestBy"
              hide-name="true"
            />
            <span v-else>{{ $t('Web Entry') }}</span>
          </td>
        </tr>
        <tr v-if="request.participants.length">
          <td aria-colindex="1" role="cell" class="pl-3">
            <span class="font-weight-normal"> {{ $t("Participants") }}: </span>
          </td>
          <td aria-colindex="2" role="cell">
            <avatar-image
              size="25"
              class="d-inline-flex pull-left align-items-center"
              :input-data="participants"
              hide-name="true"
            />
          </td>
        </tr>
      </tbody>
    </table>
    <ul class="list-group list-group-flush w-100">
      <template v-if="canCancel && request.status === 'ACTIVE'">
        <li class="list-group-item">
          <button
            type="button"
            class="btn btn-outline-danger btn-block"
            aria-haspopup="dialog"
            @click="onCancel"
          >
            <i class="fas fa-stop-circle" />
            {{ $t('Cancel') }}
          </button>
        </li>
      </template>
      <li
        v-if="canManuallyComplete"
        class="list-group-item"
      >
        <button
          type="button"
          class="btn btn-outline-success btn-block"
          data-toggle="modal"
          @click="completeRequest"
        >
          <i class="fas fa-stop-circle" />
          {{ $t('Complete') }}
        </button>
      </li>
      <li
        v-if="canRetry"
        class="list-group-item"
      >
        <button
          id="retryRequestButton"
          type="button"
          class="btn btn-outline-info btn-block"
          data-toggle="modal"
          :disabled="retryDisabled"
          @click="retryRequest">
          <i class="fas fa-sync" />
          {{ $t('Retry') }}
        </button>
      </li>
      <li
        v-if="eligibleRollbackTask"
        class="list-group-item"
      >
        <button
          id="retryRequestButton"
          type="button"
          class="btn btn-outline-info btn-block"
          data-toggle="modal"
          @click="rollback(errorTask.id, eligibleRollbackTask.element_name)"
        >
          <i class="fas fa-undo" /> {{ $t('Rollback') }}
        </button>
        <small>{{ $t('Rollback to task') }}: <b>{{ eligibleRollbackTask.element_name }}</b> ({{ eligibleRollbackTask.element_id }})</small>
      </li>
    </ul>
  </div>
</template>

<script>
import AvatarImage from "../../components/AvatarImage.vue";

Vue.component("AvatarImage", AvatarImage);

export default {
  props: ["request", "values"],
  data() {
    const authValues = JSON.parse(this.values);
    return {
      userRequested: [],
      retryDisabled: false,
      canCancel: authValues.canCancel,
      canManuallyComplete: authValues.canManuallyComplete,
      canRetry: authValues.canRetry,
      eligibleRollbackTask: authValues.eligibleRollbackTask,
      errorTask: authValues.errorTask,
      disabled: false,
    };
  },
  computed: {
    /**
     * Get the list of participants in the request.
     *
     */
    participants() {
      return this.request.participants;
    },
    /*
    * Get title for header by status
    */
    statusLabel() {
      const status = {
        ACTIVE: "In Progress",
        COMPLETED: "Completed",
        CANCELED: "Canceled",
        ERROR: "Error",
      };

      return status[this.request.status.toUpperCase()];
    },
    /*
    * Get class for header by status
    */
    classStatusCard() {
      const header = {
        ACTIVE: "bg-success",
        COMPLETED: "bg-secondary",
        CANCELED: "bg-danger",
        ERROR: "bg-danger",
      };
      return `card-header border-0 text-capitalize text-white  ${header[this.request.status.toUpperCase()]}`;
    },
    /*
    * Get user requester
    */
    requestBy() {
      return [this.request.user];
    },
    /*
    * Get end date
    */
    statusDate() {
      const status = {
        ACTIVE: "N/A",
        COMPLETED: this.moment(this.request.completed_at).format(),
        CANCELED: this.moment(this.request.updated_at).format(),
        ERROR: this.moment(this.request.updated_at).format(),
      };

      return status[this.request.status.toUpperCase()];
    },
    /*
    * Get status date label
    */
    statusDateLabel() {
      const status = {
        ACTIVE: "End",
        COMPLETED: "End",
        CANCELED: "Canceled",
        ERROR: "Error",
      };

      return status[this.request.status.toUpperCase()];
    },
    /*
    * Get start date
    */
    startDate() {
      return this.request.created_at;
    },
  },
  methods: {
    /*
    * Get class by status
    */
    requestStatusClass(status) {
      const statusLower = status.toLowerCase();
      const bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        error: "text-danger",
        draft: "text-warning",
        archived: "text-info",
        completed: "text-primary",
      };
      return `fas fa-circle ${bubbleColor[statusLower]} small`;
    },
    /*
    * Cancel request
    */
    okCancel() {
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.put(`requests/${this.request.id}`, {
        status: 'CANCELED',
      }).then(response => {
        ProcessMaker.alert(this.$t('The request was canceled.'), 'success');
        window.location.reload();
      }).catch(error => {
        this.disabled = false;
      });
    },
    /*
    * Ask of cancel request
    */
    onCancel() {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want cancel this request?"),
        "",
        () => {
          this.okCancel();
        },
      );
    },
    /*
    * Complete request manually
    */
    completeRequest() {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to complete this request?"),
        "",
        () => {
          ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
            status: "COMPLETED",
          }).then(() => {
            ProcessMaker.alert(this.$t("Request Completed"), "success");
            window.location.reload();
          });
        },
      );
    },
    /*
    * Retry request
    */
    retryRequest() {
      const apiRequest = () => {
        this.retryDisabled = true;
        let success = true;

        ProcessMaker.apiClient.put(`requests/${this.requestId}/retry`).then(response => {
          if (response.status !== 200) {
            return;
          }

          const message = response.data.message;
          success = response.data.success || false;

          if (success) {
            if (Array.isArray(message)) {
              message.foreach((line) => {
                ProcessMaker.alert(this.$t(line), "success");
              });
            }
          } else {
            ProcessMaker.alert(this.$t("Request could not be retried"), "danger")
          }
        }).finally(() => setTimeout(() => window.location.reload(), success ? 3000 : 1000))
      };

      ProcessMaker.confirmModal(
        this.$t("Confirm"),
        this.$t("Are you sure you want to retry this request?"),
        "default",
        apiRequest,
      );
    },
  },
};
</script>

<style>
  .modal-dialog .custom {
    position: absolute;
    bottom: 0;
    min-width: 100%;
  }
</style>
