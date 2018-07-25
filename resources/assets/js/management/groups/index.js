import Vue from 'vue'
import GroupsListing from './components/GroupsListing'

// Bootstrap our Designer application
new Vue({
  el: '#groups-listing',
  data: {
      filter: '' ,
      addGroupTitle: '',
      addGroupStatus: 'ACTIVE'
  },
  components: { GroupsListing },
  methods: {
    showAddModal() {
      this.$refs.addModal.show();
    },
    hideAddModal() {
      this.$refs.addModal.hide();
    },
    submitAdd() {
      window.ProcessMaker.apiClient.post('groups', {
        'title': this.addGroupTitle,
        'status': this.addGroupStatus
      })
      .then((response) => {
        // Close modal
        this.$refs.addModal.hide();
        // Status is 200, so let's just change our sort and sort direction and then fetch
        this.$refs.groupsListing.dataManager([
          {
            field: 'created_at',
            direction: 'desc'
          }
        ])

      })
      .catch((err) => {
      })
    }
  }
});


