<template>
  <div>
    <div class="recommendation" :class="{ 'dashboard': dashboard }" v-if="userRecommendation">
      <div class="recommendation-navigation" v-if="userRecommendations.length > 1">
        <a href="#" @click.prevent="previous" :class="{ 'disabled': hasPrevious }">
          <i class="fas fa-caret-left" />
        </a>
        <div>{{ currentIndex + 1 }} of {{ userRecommendations.length }}</div>
        <a href="#" @click.prevent="next" :class="{ 'disabled': hasNext }">
          <i class="fas fa-caret-right" />
        </a>
      </div>
      <div class="recommendation-title">
        <div class="recommendation-name" v-if="!dashboard">
          {{ name }}
        </div>
        <a href="#" @click.prevent="filter">{{ description }}</a>
      </div>
      <div class="recommendation-actions" v-if="!dashboard">
        <b-button
          v-if="userRecommendation.recommendation.actions.includes('mark_as_priority')"
          class="ml-2"
          variant="outline-secondary"
          @click="markAsPriority"
          >
          {{ markAsPriorityLabel }}
        </b-button>
        <b-button
          v-if="userRecommendation.recommendation.actions.includes('reassign_to_user')"
          class="ml-2"
          variant="outline-secondary"
          @click="reassignToUser"
          >
          {{ reassignToUserLabel }}
        </b-button>

        <b-dropdown variant="outline-secondary" class="ml-2" no-caret right boundary="window">
          <template #button-content>
            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
          </template>
          <b-dropdown-item @click="createRule">{{ $t('Create a rule based on this suggestion') }}</b-dropdown-item>
          <b-dropdown-divider />
          <b-dropdown-item @click="dismiss">{{ $t('Dismiss This Suggestion') }}</b-dropdown-item>
          <b-dropdown-item @click="dismissAll">{{ $t('Dismiss All') }}</b-dropdown-item>
        </b-dropdown>

        <a href="#" class="ml-2 recommendation-dismiss" @click="dismiss">
          <i class="fa fa-times" aria-hidden="true"></i>
        </a>
      </div>

      <div class="recommendation-actions" v-if="dashboard">
        <b-dropdown variant="outline-secondary" class="ml-2" no-caret right boundary="window" menu-class="w-50">
          <template #button-content>
            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
          </template>
          <b-dropdown-item @click="dismiss">{{ $t('Dismiss This Suggestion') }}</b-dropdown-item>
          <b-dropdown-item @click="dismissAll">{{ $t('Dismiss All') }}</b-dropdown-item>
        </b-dropdown>
      </div>
    </div>

    <b-modal id="confirm" :title="$t('Confirmation')">
      <template v-if="loadingMode === 'timeout'">
        {{ $t('This actions is taking longer than expected. We will continue updating your tasks in the background.') }}
      </template>
      <template v-else>
        <p v-html="confirmText"></p>
        <user-select v-if="action === 'reassign_to_user'" :label="false" @input="toUserId = $event" />
        <div class="message" v-if="message">{{ message }}</div>
      </template>

      <template #modal-footer="{ cancel }">
        <b-button @click="cancel()" class="" v-if="loadingMode === null">{{ $t('Cancel') }}</b-button>
        <b-button @click="loadingMode === 'timeout' ? cancel() : onConfirm()" variant="primary">
          <template v-if="loadingMode === 'loading'">
            <b-spinner small />
          </template>
          <template v-else>
            {{ $t('Ok') }}
          </template>
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
import UserSelect from '../processes/modeler/components/inspector/UserSelect.vue';

export default {
  components: {
    UserSelect
  },
  data() {
    return {
      userRecommendations: [],
      action: null,
      confirmText: null,
      toUserId: null,
      currentUserRecommendationId: null,
      loadingMode: null,
      message: null,
      timeout: null,
    }
  },
  props: {
    dashboard: {
      type: Boolean,
      default: false,
    }
  },
  computed: {
    userRecommendation() {
      return this.userRecommendations.find(recommendation => recommendation.id === this.currentUserRecommendationId);
    },
    name() {
      return this.$t(this.userRecommendation.recommendation.name, { count: this.userRecommendation.count });
    },
    description() {
      return this.$t(this.userRecommendation.recommendation.description, { count: this.userRecommendation.count })
    },
    markAsPriorityLabel() {
      return this.$t('Mark ({{count}}) as priority', { count: this.userRecommendation.count })
    },
    reassignToUserLabel() {
      return this.$t('Reassign ({{count}})', { count: this.userRecommendation.count })
    },
    currentIndex() {
      return this.userRecommendations.findIndex(recommendation => recommendation.id === this.currentUserRecommendationId)
    },
    hasPrevious() {
      return this.currentIndex > 0
    },
    hasNext() {
      return this.currentIndex < this.userRecommendations.length - 1
    }
  },
  mounted() {
    this.fetchRecommendations();
    this.listenForTaskListUpdates();
  },
  methods: {
    listenForTaskListUpdates() {
      const channel = `ProcessMaker.Models.User.${window.ProcessMaker?.user?.id}`;
      const event = ".TasksUpdated";
      window.Echo.private(channel).listen(
        event,
        (response) => {
          this.loadingMode = null;
          clearTimeout(this.timeout);
          this.message = response.message || null;
          if (!this.message) {
            this.$bvModal.hide('confirm');
            this.removeSelectedRecommendation();
          }
        },
      );
    },
    next() {
      if (this.currentIndex < this.userRecommendations.length - 1) {
        this.currentUserRecommendationId = this.userRecommendations[this.currentIndex + 1].id;
      }
    },
    previous() {
      if (this.currentIndex > 0) {
        this.currentUserRecommendationId = this.userRecommendations[this.currentIndex - 1].id;
      }
    },
    fetchRecommendations() {
      ProcessMaker.apiClient.get("recommendations").then((response) => {
        this.userRecommendations = response.data.data;
        this.filterQueryRecommendation();
        if (!this.currentUserRecommendationId) {
          this.currentUserRecommendationId = this.userRecommendations[0]?.id;
        }
      });
    },
    filterQueryRecommendation() {
      const urlParams = new URLSearchParams(window.location.search);
      const id = Number(urlParams.get('filter_user_recommendation'));
      if (id) {
        this.currentUserRecommendationId = id;
        this.filter();
      }
    },
    dismiss() {
      this.action = 'dismiss';
      this.update();
    },
    dismissAll() {
      const promises = this.userRecommendations.map(userRecommendation => {
        return this.callApi(userRecommendation.id, { action: 'dismiss' });
      });
      Promise.all(promises).then(() => {
        this.userRecommendations = [];
      });
    },
    createRule() {
      const payload = {
        name: this.userRecommendation.recommendation.name.replace(/[^a-zA-Z0-9 ]/g, ''),
        advanced_filter: {
          filters: this.userRecommendation.recommendation.advanced_filter,
          order: {by: "id", direction: "desc"}
        },
        data: {},
      };

      return ProcessMaker.apiClient.post('tasks/rules', payload)
        .then((response) => {
          this.dismiss().then(() => {
            const id = response.data.id;
            window.location.href = `/tasks/rules/${id}`
          });
        });
    },
    reassignToUser() {
      this.action = 'reassign_to_user';
      this.confirmText = this.$t(
        'Reassign <strong>({{count}}) tasks</strong> to:',
        { count: this.userRecommendation.count}
      );
      this.toUserId = null;
      this.$bvModal.show('confirm');
    },
    markAsPriority() {
      this.action = 'mark_as_priority';
      this.confirmText = this.$t(
        'Are you sure you want to mark <strong>({{count}}) tasks</strong> as priority?',
        { count: this.userRecommendation.count }
      );
      this.$bvModal.show('confirm');
    },
    onConfirm() {
      if (this.loadingMode === 'loading') {
        return;
      }

      let params = {};
      if (this.action === 'reassign_to_user') {
        this.update({ to_user_id: this.toUserId });
        return;
      }
      this.update(params);
    },
    update(params = {}) {
      if (!this.userRecommendation || !this.action) {
        return;
      }

      this.message = null;
      this.loadingMode = 'loading';
      this.timeout = setTimeout(() => {
        this.loadingMode = 'timeout';
        this.removeSelectedRecommendation();
      }, 10 * 1000);

      return this.callApi(
        this.userRecommendation.id, 
        {
          action: this.action,
          ...params
        }
      ).then(() => {
      });
    },
    removeSelectedRecommendation() {
      this.userRecommendations = this.userRecommendations.filter(r => r.id !== this.userRecommendation.id);
    },
    callApi(id, params) {
      return ProcessMaker.apiClient.put(
        "recommendations/" + id, params
      );
    },
    filter() {
      const filter = this.userRecommendation.recommendation.advanced_filter;
      filter.push({ subject: { type: "Status" }, operator: "=", value: "In Progress" });
      if (this.dashboard) {
        window.location.href = `/tasks?filter_user_recommendation=${this.userRecommendation.id}`
      } else {
        this.$root.$emit('load-with-filter', filter);
      }
    }
  }
}
</script>

<style scoped lang="scss">

@import '~styles/variables';

.recommendation {
  display: flex;
  padding: 10px;
  border: 2px solid #9AC2E5;
  border-radius: 5px;
  margin-bottom: 10px;
  align-items: center;
  background-color: #F2F8FC;
  overflow: hidden;
}
.recommendation.dashboard {
  border: none;
  margin-bottom: 0;
}
.recommendation-title {
  flex-grow: 1;
  min-width: 0;
  
  a {
    display: block;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    min-width: 0;
  }
}
.recommendation-name {
  font-weight: 600;
  color: #566877;
}
.recommendation-actions {
  display: flex;
  align-items: center;
}
.recommendation-actions :deep(.btn) {
  background-color: #FEFEFE;
  border-color: #CDDEEE;
  text-transform: none;
  flex-shrink: 0;
}

.dashboard .recommendation-actions :deep(.dropdown-toggle) {
  background-color: transparent;
}

.recommendation-actions :deep(.btn-outline-secondary:hover),
.recommendation-actions :deep(.btn-outline-secondary.dropdown-toggle),
.recommendation-dismiss {
  color: #566877;
}

.recommendation-dismiss {
  margin-right: 10px;
}

.recommendation-actions :deep(.dropdown-item) {
  padding-top: 10px;
  padding-bottom: 10px;
}

.recommendation-navigation {
  display: flex;
  align-items: center;
  min-width: 110px;

  a, div {
    display: block;
    margin: 0 7px;
    color: #566877;
  }

  i {
    font-size: 1.5em;
  }
}
.dashboard .recommendation-navigation a {
  padding-top: 5px
}
</style>