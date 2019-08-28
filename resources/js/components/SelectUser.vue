<template>
  <multiselect
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
    @open="loadUsers"
  >
    <template slot="noResult">
      <slot name="noResult">{{ $t('No elements found. Consider changing the search query.') }}</slot>
    </template>
    <template slot="noOptions">
      <slot name="noOptions">{{ $t('No Data Available') }}</slot>
    </template>
  </multiselect>
</template>

<script>
export default {
  props: {
    value: null,
    multiple: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      users: []
    };
  },
  methods: {
    change(value) {
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
