import { api } from "../variables";

export const formatActiveTasks = (value) => value.map((task) => `${task.element_name}`).join(", ");

// export const transformData = (dataInput) => {
//   const data = _.cloneDeep(dataInput);
//   // Clean up fields for meta pagination so vue table pagination can understand
//   data.meta.last_page = data.meta.total_pages;
//   data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
//   data.meta.to = data.meta.from + data.meta.count;
//   for (let record of data.data) {
//     if (record.active_tasks) {
//       record.current_task = formatActiveTasks(record.active_tasks);
//     }
//   };
//   return data;
// };

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
