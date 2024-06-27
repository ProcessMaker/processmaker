<template>
  <div>
    <div class="recommendation" v-for="(userRecommendation, index) in userRecommendations" :key="index">
      <div class="title">
        <div class="name">
          {{ name(userRecommendation) }}
        </div>
        <a href="#" @click="filter(userRecommendation)">{{ description(userRecommendation) }}</a>
      </div>
      <div class="actions">
        <b-button
          v-if="userRecommendation.recommendation.actions.includes('mark_as_priority')"
          class="ml-2"
          variant="outline-secondary"
          @click="markAsPriority(userRecommendation)"
          >
          {{ markAsPriorityLabel(userRecommendation) }}
        </b-button>
        <b-button
          v-if="userRecommendation.recommendation.actions.includes('reassign_to_user')"
          class="ml-2"
          variant="outline-secondary"
          @click="reassignToUser(userRecommendation)"
          >
          {{ reassignToUserLabel(userRecommendation) }}
        </b-button>

        <b-dropdown variant="outline-secondary" class="ml-2" no-caret>
          <template #button-content>
            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
          </template>
          <b-dropdown-item>{{ $t('Create a rule based on this suggestion') }}</b-dropdown-item>
          <b-dropdown-divider></b-dropdown-divider>
          <b-dropdown-item>{{ $t('Dismiss This Suggestion') }}</b-dropdown-item>
          <b-dropdown-item>{{ $t('Dismiss All') }}</b-dropdown-item>
        </b-dropdown>

        <a href="#" class="ml-2" @click="dismiss(userRecommendation)">
          <i class="fa fa-times" aria-hidden="true"></i>
        </a>
      </div>
    </div>

    <b-modal id="confirm" :title="$t('Confirmation')" @ok="onConfirm">
      <p class="" v-html="confirmText"></p>
      <user-select v-if="action === 'reassign_to_user'" :label="false" @input="toUserId = $event" />
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
      userRecommendationId: null,
      confirmText: null,
      toUserId: null,
    }
  },
  mounted() {
    this.fetchRecommendations();
  },
  methods: {
    fetchRecommendations() {
      ProcessMaker.apiClient.get("recommendations").then((response) => {
        this.userRecommendations = response.data.data;
      });
    },
    name(userRecommendation) {
      return this.$t(userRecommendation.recommendation.name, { count: userRecommendation.count });
    },
    description(userRecommendation) {
      return this.$t(userRecommendation.recommendation.description, { count: userRecommendation.count })
    },
    markAsPriorityLabel(userRecommendation) {
      return this.$t('Mark ({{count}}) as priority', { count: userRecommendation.count })
    },
    reassignToUserLabel(userRecommendation) {
      return this.$t('Reassign ({{count}})', { count: userRecommendation.count })
    },
    dismiss(userRecommendation) {
      this.userRecommendationId = userRecommendation.id;
      this.action = 'dismiss';
      this.update().then(() => {
        this.userRecommendations = this.userRecommendations.filter(r => r.id !== userRecommendation.id);
      });
    },
    reassignToUser(userRecommendation) {
      this.userRecommendationId = userRecommendation.id;
      this.action = 'reassign_to_user';
      this.confirmText = this.$t(
        'Reassign <strong>({{count}}) tasks</strong> to:',
        { count: userRecommendation.count}
      );
      this.toUserId = null;
      this.$bvModal.show('confirm');
    },
    markAsPriority(userRecommendation) {
      this.userRecommendationId = userRecommendation.id;
      this.action = 'mark_as_priority';
      this.confirmText = this.$t(
        'Are you sure you want to mark <strong>({{count}}) tasks</strong> as priority?',
        { count: userRecommendation.count }
      );
      this.$bvModal.show('confirm');
    },
    onConfirm() {
      let params = {};
      if (this.action === 'reassign_to_user') {
        this.update({ to_user_id: this.toUserId });
        return;
      }
      this.update(params);
    },
    update(params = {}) {
      if (!this.userRecommendationId || !this.action) {
        return;
      }
      return ProcessMaker.apiClient.put(
        "recommendations/" + this.userRecommendationId, 
        {
          action: this.action,
          ...params
        }
      );
    },
    filter(userRecommendation) {
      const filter = userRecommendation.recommendation.advanced_filter;
      filter.push({ subject: { type: "Status" }, operator: "=", value: "In Progress" });
      this.$root.$emit('load-with-filter', filter);
    }
  }
}
</script>

<style scoped lang="scss">
.recommendation {
  display: flex;
  padding: 10px;
  border: 2px solid #9AC2E5;
  border-radius: 5px;
  margin-bottom: 10px;
  align-items: center;
  background-color: #F2F8FC;
}
.title {
  flex-grow: 1;
}
.name {
  font-weight: 600;
}
.actions :deep(.btn) {
  background-color: #FEFEFE;
  border-color: #CDDEEE;
  text-transform: none;
}
</style>