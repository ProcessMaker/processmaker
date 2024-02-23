<template>
  <div>
    <div class="form-group">
      <label for="select_type">{{ $t('Type') }}</label>
      <select
        id="select_type"
        class="form-control"
        :disabled="disabled"
        :value="assignmentGetter"
        @input="assignmentSetter"
      >
        <option value="">
          {{ $t('Select...') }}
        </option>
        <option value="user">
          {{ $t('User') }}
        </option>
        <option value="group">
          {{ $t('Group') }}
        </option>
        <option value="process_manager">
          {{ $t('Process Manager') }}
        </option>
      </select>
      <small
        v-if="helper"
        class="form-text text-muted"
      >{{ $t(helper) }}</small>
    </div>

    <div
      v-if="assignmentGetter && assignmentGetter !== 'process_manager'"
      class="form-group"
    >
      <label class="text-capitalize">{{ $t(assignmentGetter) }}</label>
      <multiselect
        v-model="content"
        :aria-label="$t(assignmentGetter)"
        track-by="id"
        label="name"
        :class="{'border border-danger':error}"
        :loading="loading"
        :placeholder="$t('type here to search')"
        :options="options"
        :multiple="false"
        :show-labels="false"
        :searchable="true"
        :internal-search="false"
        :disabled="disabled"
        @open="load()"
        @search-change="load"
      >
        <template slot="noResult">
          {{ $t('No elements found. Consider changing the search query.') }}
        </template>
        <template slot="noOptions">
          {{ $t('No Data Available') }}
        </template>
      </multiselect>
      <small
        v-if="error"
        class="text-danger"
      >{{ error }}</small>
      <small
        v-if="helperText"
        class="form-text text-muted"
      >{{ $t(helperText) }}</small>
    </div>
  </div>
</template>

<script>
import "@processmaker/vue-multiselect/dist/vue-multiselect.min.css";

export default {
  props: ["value", "label", "helper", "userHelper", "groupHelper", "property"],
  data() {
    return {
      content: null,
      options: [],
      loading: false,
      error: "",
      type: "",
      disabled: false,
    };
  },
  computed: {
    helperText() {
      return this.type === "user" ? this.userHelper : this.groupHelper;
    },
    assignmentGetter() {
      const node = this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      const value = _.get(node, "assignment");
      this.type = value;
      return value;
    },
  },
  watch: {
    content: {
      handler() {
        if (this.type === "user" && this.content) {
          this.assignedUserSetter(this.content.id);
        } else if (this.type === "group" && this.content) {
          this.assignedGroupSetter(this.content.id);
        }
      },
    },
    value: {
      immediate: true,
      handler() {
        this.loadAssigned();
      },
    },
  },
  mounted() {
    this.loadAssigned();

    this.$root.$on("disable-assignment-settings", (val) => {
      this.disabled = val;
    });
  },
  methods: {
    load(filter) {
      this.options = [];
      if (this.type === "user") {
        this.loadUsers(filter);
      } else if (this.type === "group") {
        this.loadGroups(filter);
      }
    },
    loadUsers(filter) {
      this.loading = true;
      ProcessMaker.apiClient
        .get(`users?order_direction=asc${typeof filter === "string" ? `&filter=${filter}` : ""}`)
        .then((response) => {
          this.loading = false;
          this.options = response.data.data.map((item) => ({
            id: item.id,
            name: item.fullname,
          }));
        })
        .catch((err) => {
          this.loading = false;
        });
    },
    loadGroups(filter) {
      this.loading = true;
      ProcessMaker.apiClient
        .get(`groups?order_direction=asc&status=active${typeof filter === "string" ? `&filter=${filter}` : ""}`)
        .then((response) => {
          this.loading = false;
          this.options = response.data.data.map((item) => ({
            id: item.id,
            name: item.name,
          }));
        })
        .catch((err) => {
          this.loading = false;
        });
    },
    loadAssigned() {
      const node = this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      let value = _.get(node, "assignment");
      this.type = value;
      this.content = null;
      if (this.type === "user") {
        value = _.get(node, "assignedUsers");
        if (!value) {
          return;
        }
        this.loading = true;
        ProcessMaker.apiClient
          .get(`users/${value}`)
          .then((response) => {
            this.loading = false;
            this.content = {
              id: response.data.id,
              name: response.data.fullname,
            };
          })
          .catch((error) => {
            this.loading = false;
            if (error.response.status == 404) {
              this.content = "";
              this.error = this.$t("Selected user not found");
            }
          });
      } else if (this.type === "group") {
        value = _.get(node, "assignedGroups");
        if (!value) {
          return;
        }
        this.loading = true;
        ProcessMaker.apiClient
          .get(`groups/${value}`)
          .then((response) => {
            this.loading = false;
            this.content = {
              id: response.data.id,
              name: response.data.name,
            };
          })
          .catch((err) => {
            this.loading = false;
            if (error.response.status == 404) {
              this.content = "";
              this.error = this.$t("Selected group not found");
            }
          });
      }
    },

    assignedUserSetter(id) {
      const node = this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      let value = _.get(node, "assignedUsers");
      this.$set(node, "assignedUsers", id);
      value = _.get(node, "assignedGroups");
      this.$set(node, "assignedGroups", "");
    },
    assignedGroupSetter(id) {
      const node = this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      let value = _.get(node, "assignedUsers");
      this.$set(node, "assignedUsers", "");
      value = _.get(node, "assignedGroups");
      this.$set(node, "assignedGroups", id);
    },
    assignmentSetter(event) {
      this.type = event.target.value;
      this.content = null;
      const node = this.$root.$children[0].$refs.modeler.highlightedNode.definition;
      this.$set(node, "assignment", this.type);
      this.load();
    },
  },
};
</script>

<style lang="scss" scoped>
    .form-group {
      padding: 0px;
    }
</style>
