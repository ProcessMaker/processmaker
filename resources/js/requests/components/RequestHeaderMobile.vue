<template>
  <div
    v-if="statusLabel"
    class="card border-0"
  >
    <table
      class="table b-table m-0 border-0"
      aria-label="fileDetails"
      role="table"
    >
      <!-- Need header for Sonar problems, it is not needed in the view-->
      <thead
        :style="classStatusCard"
        class="status-header-class"
        @click="toggleCollapse"
      >
      <tr>
          <th class="d-flex align-items-center pl-3 border-0 mt-1">
            <div class="d-flex flex-column ml-2">
              <h4
                class="status-class-text"
                :style="textColorClass"
                style="padding: 0; line-height: 1"
              >
                {{ $t(statusLabel) }}
              </h4>
              <p class="small mb-1 detail-header-class">{{ $t("Start") }}: {{ moment(startDate).format() }}</p>
            </div>
          </th>
          <th class="border-0 text-right pr-3 align-middle">
            <div class="icon-class">
              <i :class="iconClass"></i>
            </div>
          </th>
        </tr>
      </thead>
      <transition-group
        name="collapse"
        tag="tbody"
      >
        <tr
          v-if="!collapsed"
          key="content"
        >
          <td colspan="2" style="padding: 0%;">
            <table class="table m-0 border-0" aria-describedby="table-description">
              <thead v-if="false">
                <tr>
                  <th scope="col" class="pl-0"></th>
                  <th scope="col"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="request.case_number">
                  <td
                    aria-colindex="1"
                    role="cell"
                    class="pl-3"
                  >
                    <span class="custom-font-subject">
                      {{ $t("CASE #") }}
                    </span>
                  </td>
                  <td
                    aria-colindex="2"
                    role="cell"
                    class="custom-font-text"
                  >
                    <span>{{ request.case_number}}</span>
                  </td>
                </tr>
                <tr v-if="request.case_title" class="row-class">
                  <td
                    aria-colindex="1"
                    role="cell"
                    class="pl-3"
                  >
                    <span class="custom-font-subject">
                      {{ $t("CASE NAME") }}
                    </span>
                  </td>
                  <td
                    aria-colindex="2"
                    role="cell"
                    class="custom-font-text"
                  >
                    <span>{{ request.case_title}}</span>
                  </td>
                </tr>
                <tr v-if="request.user_id">
                  <td
                    aria-colindex="1"
                    role="cell"
                    class="pl-3"
                  >
                    <span class="custom-font-subject">
                      {{ $t("REQUESTED BY") }}:
                    </span>
                  </td>
                  <td
                    aria-colindex="2"
                    role="cell"
                    class="custom-font-text"
                  >
                    <avatar-image
                      v-if="userRequested"
                      size="32"
                      class="d-inline-flex pull-left align-items-center"
                      :input-data="requestBy"
                      hide-name="true"
                    />
                    <span v-else>{{ $t("Web Entry") }}</span>
                  </td>
                </tr>
                <tr v-if="request.participants.length" class="row-class">
                  <td
                    aria-colindex="1"
                    role="cell"
                    class="pl-3"
                  >
                    <span class="custom-font-subject">
                      {{ $t("Participants") }}:
                    </span>
                  </td>
                  <td
                    aria-colindex="2"
                    role="cell"
                    class="custom-font-text"
                  >
                  <div class="avatar-wrapper">
                    <avatar-image
                      v-for="(participant, index) in participants"
                      :key="index"
                      size="32"
                      class="avatar"
                      :input-data="participant"
                      hide-name="true"
                      :custom-style="borderRoundedWhite"
                    />
                    <div v-if="hiddenParticipantsCount > 0" class="more-avatars">
                      ...
                    </div>
                  </div>
                  </td>
                </tr>
                <tr v-if="request.created_at">
                  <td
                    aria-colindex="1"
                    role="cell"
                    class="pl-3"
                  >
                    <span class="custom-font-subject">
                      {{ $t("STARTED") }}
                    </span>
                  </td>
                  <td
                    aria-colindex="2"
                    role="cell"
                    class="custom-font-text"
                  >
                    <span>{{ moment(startDate).format() }}</span>
                  </td>
                </tr>
                <tr class="row-class">
                  <td
                    aria-colindex="1"
                    role="cell"
                    class="pl-3"
                  >
                    <span class="custom-font-subject">
                      {{ $t("COMPLETED") }}
                    </span>
                  </td>
                  <td
                    aria-colindex="2"
                    role="cell"
                    class="custom-font-text"
                  >
                    <span>{{ completedDate ? moment(completedDate).format() : '-' }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
            <ul class="list-group list-group-flush w-100">
              <template v-if="canCancel && request.status === 'ACTIVE'">
                <li class="list-group-item">
                  <button
                    type="button"
                    class="btn btn-outline-danger btn-block cancel-class-text"
                    aria-haspopup="dialog"
                    @click="onCancel"
                  >
                    <i class="fas fa-ban" />
                    {{ $t("Cancel Case") }}
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
                  {{ $t("Complete") }}
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
                  @click="retryRequest"
                >
                  <i class="fas fa-sync" />
                  {{ $t("Retry") }}
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
                  @click="
                    rollback(errorTask.id, eligibleRollbackTask.element_name)
                  "
                >
                  <i class="fas fa-undo" /> {{ $t("Rollback") }}
                </button>
                <small
                  >{{ $t("Rollback to task") }}:
                  <b>{{ eligibleRollbackTask.element_name }}</b> ({{
                    eligibleRollbackTask.element_id
                  }})</small
                >
              </li>
            </ul>
          </td>
        </tr>
      </transition-group>
    </table>
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
      collapsed: false,
      borderRoundedWhite: "border: 2px solid white;",
      minVisible: 5,
    };
  },
  computed: {
    visibleParticipants() {
      return this.participants.length > this.minVisible ? this.participants.slice(0, this.minVisible) : this.participants;
    },
    hiddenParticipantsCount() {
      return this.participants.length > this.minVisible ? this.participants.length - this.minVisible : 0;
    },
    textColorClass() {
      const colors = {
        ACTIVE: "#4EA075",
        COMPLETED: "#556271",
        CANCELED: "#ED4858",
        ERROR: "#ED4858",
      };
      return {
        color: colors[this.request.status.toUpperCase()] || "black",
      };
    },
    iconClass() {
      return this.collapsed ? "fas fa-caret-right" : "fas fa-caret-down";
    },
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
        ACTIVE: "#E0F5E7",
        COMPLETED: "#E0E0E0",
        CANCELED: "#F5E0E0",
        ERROR: "#F5E0E0",
      };
      return {
        backgroundColor:
          header[this.request.status.toUpperCase()] || "transparent",
      };
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

    completedDate() {
      return this.request.completed_at;
    },
  },
  methods: {
    toggleCollapse() {
      this.collapsed = !this.collapsed;
    },
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
      ProcessMaker.apiClient
        .put(`requests/${this.request.id}`, {
          status: "CANCELED",
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The request was canceled."), "success");
          window.location.reload();
        })
        .catch((error) => {
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
        }
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
          ProcessMaker.apiClient
            .put(`requests/${this.requestId}`, {
              status: "COMPLETED",
            })
            .then(() => {
              ProcessMaker.alert(this.$t("Request Completed"), "success");
              window.location.reload();
            });
        }
      );
    },
    /*
     * Retry request
     */
    retryRequest() {
      const apiRequest = () => {
        this.retryDisabled = true;
        let success = true;

        ProcessMaker.apiClient
          .put(`requests/${this.requestId}/retry`)
          .then((response) => {
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
              ProcessMaker.alert(
                this.$t("Request could not be retried"),
                "danger"
              );
            }
          })
          .finally(() =>
            setTimeout(() => window.location.reload(), success ? 3000 : 1000)
          );
      };

      ProcessMaker.confirmModal(
        this.$t("Confirm"),
        this.$t("Are you sure you want to retry this request?"),
        "default",
        apiRequest
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
.status-header-class {
  height: 85px;
  cursor: pointer;
}
.collapse-enter-active,
.collapse-leave-active {
  transition: max-height 0.5s ease;
}
.collapse-enter,
.collapse-leave-to {
  max-height: 0;
  overflow: hidden;
}
.detail-header-class {
  padding: 0; 
  font-family: 'Open', sans-serif;
  font-weight: 500;
  font-size: 14px;
  line-height: 21px;
  letter-spacing: -0.02%;
  color: #4C545C;
}
.status-class-text {
  font-family: 'Open', sans-serif;
  font-weight: 700;
  font-size: 22px;
  line-height: 29.96px;
  letter-spacing: -0.02%;
}
.icon-class {
  color: #6A7888;
  font-size: 20px;
}
.row-class {
  background-color: #F7F9FB;
}

.custom-font-subject {
  font-weight: 700;
  font-size: 13px;
  line-height: 19.5px;
  letter-spacing: -0.02%;
  color: #556271;
  text-transform: uppercase;
}

.custom-font-text {
  font-weight: 400;
  font-size: 15px;
  line-height: 20.43px;
  letter-spacing: -0.02%;
  color: #4C545C;
}

.cancel-class-text {
  font-weight: 400;
  font-size: 16px;
  line-height: 24px;
  letter-spacing: -0.02%;
}

.avatar-wrapper {
  display: flex;
}

.avatar-wrapper .avatar {
  margin-left: -10px;
  border: 0px solid white;
  border-radius: 50%;
}

.avatar-wrapper .avatar:first-child {
  margin-left: 0;
}

.avatar-wrapper .more-avatars {
  display: flex;
  justify-content: center;
  margin-left: -10px;
  border: 2px solid white;
  border-radius: 50%;
  width: 38px;
  height: 38px;
  background-color: #ccc;
  color: #5E5E5E;
  font-weight: bold;
  font-size: 22px;
  margin-top: 1.5%;
}
</style>
