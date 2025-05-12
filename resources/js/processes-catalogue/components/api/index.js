import { api } from "../variables";

export const getRequests = async ({
  page, perPage, orderDirection, orderBy, nonSystem, processesIManage, allInbox, pmql, filter, advancedFilter, include, statusFilter,
}) => {
  const response = await api.get("/requests", {
    params: {
      page,
      include,
      per_page: perPage,
      pmql,
      order_direction: orderDirection,
      order_by: orderBy,
      // non_system: nonSystem,
      // all_inbox: allInbox,
      // processesIManage,
      filter,
      advanced_filter: JSON.stringify(advancedFilter),
      // status_filter: statusFilter,
    },
  });

  return response.data;
};
