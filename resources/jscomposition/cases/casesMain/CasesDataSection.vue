<template>
  <div class="tw-w-full tw-space-y-4">
    <CaseFilter />

    <BadgesSection v-model="badgesData" />

    <BaseTable :columns="columnsConfig" :data="data" />

    <Pagination />
  </div>
</template>
<script>
import { defineComponent, ref, onMounted } from "vue";
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { BaseTable, Pagination } from "../../base";
import { getColumns } from "./config/columns";
import { Breadcrums } from "../../system";
import { badges } from "./config/badges";
import { getData } from "./api";

export default defineComponent({
  props: {
    listId: {
      type: String,
      default: () => "myCases",
    },
  },
  components: {
    CaseFilter,
    BadgesSection,
    BaseTable,
    Pagination,
    Breadcrums,
  },
  setup(props, { emit }) {
    const badgesData = ref(badges);
    const columnsConfig = ref();
    const data = ref();

    onMounted(async () => {
      data.value = await getData();

      columnsConfig.value = getColumns(props.listId);
    });

    return {
      columnsConfig,
      data,
      badgesData,
    };
  },
});
</script>
