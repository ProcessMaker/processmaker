<template>
  <div>
    <vuetable :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta"></vuetable> 
    <div class="float-right">
      <pagination @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
    </div>
   </div>
</template>

<script>
import Vuetable from 'vuetable-2/src/components/Vuetable'
import Pagination from '../../components/common/Pagination'

export default {
  components: {
    Vuetable,
    Pagination
  },
  data() {
    return {
      // Our listing of users
      data: [],
      page: 1,
      loading: false,
      fields: [
        {
          title: 'Username',
          name: 'username',
        },
        {
          title: 'First Name',
          name: 'firstname',
        },
        {
          title: 'Created At',
          name: 'created_at'
        },
        {
          title: 'Updated At',
          name: 'updated_at'
        }
      ]
    };
  },
  created() {
    // Use our api to fetch our user listing
    this.fetch()
  },
  methods: {
      fetch() {
          this.loading = true;
          // Load from our api client
          ProcessMaker.apiClient.get('users?page=' + this.page)
            .then((response) => {
                this.data = this.transform(response.data)
                this.loading = false
            }) 
            .catch((error) => {
                // Undefined behavior currently, show modal?

            })
      },
      transform(data) {
        // Clean up fields for meta pagination so vue table pagination can understand
        data.meta.last_page = data.meta.total_pages
        data.meta.from = (data.meta.current_page -1 ) * data.meta.per_page
        data.meta.to = (data.meta.from + data.meta.count)
        return data
      },
      onPaginationData(data) {
        this.$refs.pagination.setPaginationData(data)
      },
      onPageChange(page) {
        this.page = page
        this.fetch()
      }
  }
};
</script>

<style lang="scss" scoped>
</style>

