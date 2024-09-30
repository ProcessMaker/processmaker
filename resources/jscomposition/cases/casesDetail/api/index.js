import { api } from "../variables";

export const formatActiveTasks = (value) => value.map((task) => `
      <a href="${this.openTask(task)}">
        ${task.element_name}
      </a>
    `).join("<br/>");

export const formatStatus = (status) => {
  let color;
  let label;
  switch (status) {
    case "DRAFT":
      color = "danger";
      label = "Draft";
      break;
    case "CANCELED":
      color = "danger";
      label = "Canceled";
      break;
    case "COMPLETED":
      color = "primary";
      label = "Completed";
      break;
    case "ERROR":
      color = "danger";
      label = "Error";
      break;
    default:
      color = "success";
      label = "In Progress";
  }
  return (
    `<span class="badge badge-${color} status-${color}">${this.$t(label)}</span>`
  );
};

export const transformData = (dataInput) => {
  const data = _.cloneDeep(dataInput);
  // Clean up fields for meta pagination so vue table pagination can understand
  data.meta.last_page = data.meta.total_pages;
  data.meta.from = (data.meta.current_page - 1) * data.meta.per_page;
  data.meta.to = data.meta.from + data.meta.count;
  data.data = this.jsonRows(data.data);
  data.data.forEach(record => {
    if (record.active_tasks) {
      record.active_tasks = formatActiveTasks(record.active_tasks);
    }
    record.status = formatStatus(record.status);
    record.participants = this.formatParticipants(record.participants);
  });
  return data;
};

export const getData = async ({ params, pagination }) => {
  const response = await api.get("requests-by-case", {
    params: {
      ...params,
      ...pagination,
    },
  });

  return transformData(response.data);
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
