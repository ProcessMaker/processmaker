<template>
  <div>
    <BaseTable
      id="request-table"
      :columns="columnsConfig"
      :data="data"
    />
    <Pagination />
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { BaseTable, Pagination } from "../../../base";
import { getData } from "../api/index";
import { getColumns } from "../config/columns";

export default defineComponent({
  components: { BaseTable, Pagination },
  setup() {
    const data = ref(null);
    const columnsConfig = ref(null);

    onMounted(async () => {
      data.value = await getData();
      columnsConfig.value = getColumns("requests");
    });

    return {
      data,
      columnsConfig,
    };
  },
});
</script>
