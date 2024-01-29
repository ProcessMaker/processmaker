<template>
  <div class="form-group">
    <label>{{ $t(label) }}</label>
    <multiselect
      v-model="content"
      :aria-label="$t(label)"
      track-by="id"
      label="title"
      :class="{ 'border border-danger': error }"
      :loading="!!loading"
      :placeholder="$t('type here to search')"
      :options="options"
      :multiple="true"
      :show-labels="false"
      :searchable="true"
      :internal-search="true"
      @open="load()"
      @search-change="load"
      @select="(selected) => lastSelectedId = selected.id"
    >
      <template slot="noResult">
        {{ $t('No elements found. Consider changing the search query.') }}
      </template>
      <template slot="noOptions">
        {{ $t('No Data Available') }}
      </template>
    </multiselect>
    <div
      v-for="(error, index) in errors"
      :key="index"
      class="invalid-feedback d-block"
    >
      <small
        v-if="error"
        role="alert"
        class="text-danger"
      >{{ error }}</small>
    </div>
    <small
      v-if="error"
      role="alert"
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
  props: ["value", "errors", "label", "helper", "params", "apiGet", "apiList", "projectId"],
  data() {
    return {
      content: [],
      loading: false,
      options: [],
      error: "",
      lastSelectedId: null,
      currentProject: null,
      initialLoadExecuted: false,
    };
  },
  watch: {
    content: {
      handler() {
        if (Array.isArray(this.content)) {
          this.$emit("input", this.content.map((item) => item.id).join(","));
        } else if (this.content) {
          this.$emit("input", this.content.id);
        } else {
          this.$emit("input", "");
        }
      },
    },
    value: {
      handler(newValue, oldValue) {
        if (!_.isEqual(newValue, oldValue)) {
          this.loadSelectedOptions();
        }
      },
    },
    projectId: {
      handler(newValue, oldValue) {
        if (!_.isEqual(newValue, oldValue)) {
          this.loadSelectedOptions();
        }
      },
    },
  },
  methods: {
    loadSelectedOptions() {
      let selectedIds = this.value || [];
      if (!Array.isArray(selectedIds)) {
        selectedIds = this.value.split(",");
      }

      if (this.projectId && !selectedIds.includes(this.projectId)) {
        selectedIds.push(this.projectId);
      }

      const idsToLoad = [];
      selectedIds.forEach((id) => {
        if (!this.options.find((item) => item.id == id)) {
          idsToLoad.push(id);
        }
      });

      if (idsToLoad.length == 0) {
        return;
      }

      const params = { only_ids: idsToLoad.join(",") };
      ProcessMaker.apiClient.get(this.apiList, { params }).then((response) => {
        this.content = response.data.data;
        response.data.data.forEach((project) => {
          if (!this.options.find((item) => item.id == project.id)) {
            this.options.push(project);
          }
        });
      });
    },
    load: _.debounce(function (filter) {
      ProcessMaker.apiClient
        .get(`${this.apiList}?order_direction=asc&status=active&per_page=10${typeof filter === "string" ? `&filter=${filter}` : ""}`)
        .then((response) => {
          this.loading = false;
          response.data.data.forEach((project) => {
            if (!this.options.find((item) => item.id == project.id)) {
              this.options.push(project);
            }
          });
        })
        .catch((err) => {
          this.loading = false;
        });
    }, 300),
  },
  mounted() {
    this.load();
    if (this.projectId || this.value) {
      this.loadSelectedOptions();
    }
  },
};
</script>
