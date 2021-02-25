<template>
  <div class="settings-groups">
    <b-tabs no-fade>
      <b-tab :title="group" v-for="(group, index) in groups" :key="index">
        <b-card class="border-top-0 p-0" no-body>
          <b-card-body class="p-3">
            <settings-listing :group="group"></settings-listing>
          </b-card-body>
        </b-card>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import SettingsListing from './SettingsListing';

export default {
  components: { SettingsListing },
  data() {
    return {
      groups: [],
      url: '/settings/groups'
    };
  },
  methods: {
    apiGet() {
      return ProcessMaker.apiClient.get(this.url);
    },
  },
  mounted() {
    this.apiGet().then(response => {
      response.data.data.forEach(group => {
        this.groups.push(group.group);
      });
    });
  }
};
</script>

<style lang="scss" scoped>
  //
</style>