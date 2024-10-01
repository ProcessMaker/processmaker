<template>
  <div>
    <BaseTable
      id="completed-forms"
      :columns="columnsConfig"
      :data="data"
    >
      <template
        v-for="(item, index) in data"
        #[`container-row-${index}`]
      >
        <display-form
          :key="`display-${index}`"
          :data="item"
        />
      </template>
      <template
        v-for="(item, index) in data"
        #[`ellipsis-menu-${index}`]
      >
        <ellipsis-menu
          :key="`ellipsis-${index}`"
          :data="item"
        />
      </template>
    </BaseTable>
    <Pagination />
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { BaseTable, Pagination } from "../../../base";
import DisplayForm from "./DisplayForm.vue";
import EllipsisMenu from "./EllipsisMenu.vue";
import { getData } from "../api/index";
import { getColumns } from "../config/columns";

export default defineComponent({
  components: {
    BaseTable, Pagination, DisplayForm, EllipsisMenu,
  },
  setup() {
    const data = ref(null);
    const columnsConfig = ref(null);

    onMounted(async () => {
      data.value = await getData();
      columnsConfig.value = getColumns("completed_forms");
    });

    return {
      data,
      columnsConfig,
    };
  },
});
</script>
