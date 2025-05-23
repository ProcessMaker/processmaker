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

export const getStages = async ({ processId }) => {
  const response = await api.get(`/processes/${processId}/stages`);

  const st = [
    {
      stage_id: 0,
      stage_name: "Unmatched",
      percentage: 0,
      percentage_format: "100",
      agregation_sum: 500,
      agregation_count: 0,
    },
    {
      stage_id: 1,
      stage_name: "PI Confirmation",
      percentage: 0,
      percentage_format: "50",
      agregation_sum: 50,
      agregation_count: 0,
    },
    {
      stage_id: 2,
      stage_name: "Proposal Development",
      percentage: 0,
      percentage_format: "45",
      agregation_sum: 90000,
      agregation_count: 0,
    },
    {
      stage_id: 3,
      stage_name: "Awaiting Defense",
      percentage: 0,
      percentage_format: "30",
      agregation_sum: 60000,
      agregation_count: 0,
    },
    {
      stage_id: 4,
      stage_name: "Awaiting Results",
      percentage: 0,
      percentage_format: "25",
      agregation_sum: 40000,
      agregation_count: 0,
    },
    {
      stage_id: 5,
      stage_name: "Won",
      percentage: 0,
      percentage_format: "15",
      agregation_sum: 30000,
      agregation_count: 0,
    },
  ];

  return { data: st };
};

export const getMetrics = async ({ processId }) => {
  const response = await api.get(`/processes/${processId}/metrics`);
  return response.data;
};
