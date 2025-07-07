import { api, metricsApiEndpoint } from "../variables";

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
      filter,
      advanced_filter: JSON.stringify(advancedFilter),
    },
  });

  return response.data;
};

export const getStages = async ({ processId }) => {
  const response = await api.get(`/processes/${processId}/stage-mapping`);
  return response.data;
};

export const getMetrics = async ({ processId }) => {
  const apiDefault = metricsApiEndpoint ? `${metricsApiEndpoint.replace("{process}", processId)}` : `/processes/${processId}/metrics`;
  const response = await api.get(apiDefault);
  return response.data;
};
