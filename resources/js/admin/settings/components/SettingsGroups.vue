<template>
  <div class="settings-groups">
    <b-tabs no-fade>
      <b-tab :title="group" v-for="(group, index) in groups" :key="group">
        <b-card class="border-top-0 p-0" no-body>
          <b-card-body class="p-3">
            <settings-listing
              :group="group"
              @refresh="refresh"
              @refresh-all="refreshAll"
              ref="listings"
            ></settings-listing>
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
    refresh() {
      this.apiGet().then(response => {
        response.data.data.forEach(group => {
          if (! this.groups.includes(group.group)) {
            this.groups.push(group.group);
          }
        });
        this.groups.forEach((group, index) => {
          let match = response.data.data.find(serverGroup => serverGroup.group === group);
          if (!match) {
            this.groups.splice(index, 1);
          }
        });
        this.groups.sort();
      });
    },
    refreshAll() {
      if (Array.isArray(this.$refs.listings)) {
        this.$refs.listings.forEach(listing => {
          listing.refresh();
        })
      }
    }
  },
  mounted() {
    this.refresh();
  },
};
</script>

<style lang="scss" scoped>
  //
</style>
