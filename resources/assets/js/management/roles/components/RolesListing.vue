<template>
  <div>
    <vuetable :api-mode="false"  @vuetable:pagination-data="onPaginationData" :fields="fields" :data="data" data-path="data" pagination-path="meta"></vuetable> 
    <div class="float-right">
      <pagination :perPageSelectEnabled="true" @changePerPage="changePerPage" @vuetable-pagination:change-page="onPageChange" ref="pagination"></pagination>
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
      // Our listing of roles
      data: [],
      page: 1,
      perPage: 10,
      loading: false,
      fields: [
        {
          title: 'Name',
          name: 'name',
        },
        {
          title: 'Description',
          name: 'description',
        },
        {
            title: 'Code',
            name: 'code'
        },
        {
            title: 'Total Users Assigned',
            name: 'total_users'
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
    // Use our api to fetch our role listing
    this.fetch()
  },
  methods: {
    changePerPage(value) {
      this.perPage = value
      this.fetch()
    },
      fetch() {
          this.loading = true;
          // Load from our api client
          ProcessMaker.apiClient.get('roles?page=' + this.page + '&per_page=' + this.perPage)
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
        if(page == 'next') {
          this.page = this.page + 1;
        } else if(page == 'prev') {
          this.page = this.page - 1;
        } else {
          this.page = page
        }
        if(this.page <= 0) {
          this.page = 1;
        }
        if(this.page > this.data.meta.last_page) {
          this.page = this.data.meta.last_page;
        }
        this.fetch()
      }
  }
};
</script>

<style lang="scss" scoped>
</style>

