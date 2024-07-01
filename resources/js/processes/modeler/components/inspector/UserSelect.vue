<template>
  <div class="form-group">
    <label v-if="label !== false">{{ $t(label) }}</label>
    <multiselect
      v-model="content"
      :aria-label="$t(label)"
      track-by="id"
      label="fullname"
      :class="{'border border-danger':error}"
      :loading="loading"
      :placeholder="$t('type here to search')"
      :options="users"
      :multiple="false"
      :show-labels="false"
      :searchable="true"
      :internal-search="false"
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
      v-if="helper"
      class="form-text text-muted"
    >{{ $t(helper) }}</small>
  </div>
</template>

<script>
import "@processmaker/vue-multiselect/dist/vue-multiselect.min.css";

export default {
  props: ["value", "label", "helper", "params"],
  data() {
    return {
      content: "",
      loading: false,
      users: [],
      error: "",
    };
  },
  computed: {
    node() {
      return this.$parent.$parent.highlightedNode.definition;
    },
  },
  watch: {
    content: {
      handler() {
        if (!this.content) {
          this.$emit("input", null);
        } else {
          this.$emit("input", this.content.id);
        }
      },
    },
    value: {
      immediate: true,
      handler() {
        // Load selected item.
        if (this.value) {
          this.loading = true;
          ProcessMaker.apiClient
            .get(`users/${this.value}`)
            .then((response) => {
              this.loading = false;
              this.content = response.data;
            })
            .catch((error) => {
              this.loading = false;
              if (error.response.status == 404) {
                this.content = "";
                this.error = this.$t("Selected user not found");
              }
            });
        } else {
          this.content = "";
          this.error = "";
        }
      },
    },
  },
  methods: {
    load(filter) {
      ProcessMaker.apiClient
        .get(`users?order_direction=asc&status=active${typeof filter === "string" ? `&filter=${filter}` : ""}`)
        .then((response) => {
          this.loading = false;
          this.users = response.data.data;
        })
        .catch((err) => {
          this.loading = false;
        });
    },
  },
};
</script>
