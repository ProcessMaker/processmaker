import { api } from "../variables";

export const getData = async ({ params, pagination }) => {
  const response = await api.get("requests-by-case", {
    params: {
      ...params,
      ...pagination,
    },
  });

  return response.data.data;
};

export const getDataTask = async ({ params, pagination }) => {
  const response = await api.get("tasks-by-case/", {
    params: {
      ...params,
      ...pagination,
    },
  });

  return response.data.data;
};
