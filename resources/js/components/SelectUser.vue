<template>
  <div>
    <multiselect
      :id="'user-select-' + _uid"
      :value="value"
      @input="change"
      :placeholder="$t('Select')"
      :options="users"
      :multiple="multiple"
      track-by="id"
      :show-labels="false"
      :searchable="true"
      :internal-search="false"
      label="fullname"
      @search-change="loadUsers"
      @open="loadUsers(null)"
    >
      <template slot="noResult">
        <slot name="noResult">{{ $t('No elements found. Consider changing the search query.') }}</slot>
      </template>
      <template slot="noOptions">
        <slot name="noOptions">{{ $t('No Data Available') }}</slot>
      </template>
    </multiselect>
    <div v-if="selectionInfo" class="text-muted small mt-1">
      {{ selectionInfo }}
    </div>
    <div v-if="limitReachedMessage" class="text-warning small mt-1">
      {{ limitReachedMessage }}
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: null,
    multiple: {
      type: Boolean,
      default: false
    },
    maxSelection: {
      type: Number,
      default: null
    }
  },
  data() {
    return {
      users: []
    };
  },
  computed: {
    selectionInfo() {
      if (this.multiple && this.maxSelection && Array.isArray(this.value)) {
        const selected = this.value.length;
        const max = this.maxSelection;
        return `${selected}/${max} ${this.$t('selected')}`;
      }
      return null;
    },
    limitReachedMessage() {
      if (this.multiple && this.maxSelection && Array.isArray(this.value) && this.value.length >= this.maxSelection) {
        return this.$t('Maximum of {{max}} users can be selected', { max: this.maxSelection });
      }
      return null;
    }
  },
  methods: {
    change(value) {
      // If multiple and maxSelection is set, and value is an array
      if (this.multiple && this.maxSelection && Array.isArray(value)) {
        // If value length is greater than maxSelection, keep only the first maxSelection elements
        if (value.length > this.maxSelection) {
          value = value.slice(0, this.maxSelection);
        }
      }
      this.$emit('input', value);
    },
    loadUsers(filter) {
      window.ProcessMaker.apiClient
        .get("users" + (typeof filter === "string" ? "?filter=" + filter : ""))
        .then(response => {
          this.users = response.data.data;
        });
    }
  }
};
</script>
