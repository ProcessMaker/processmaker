import { api } from "../variables";

export const getData = async () => {
  const objectsList = [];

  for (let i = 0; i <= 31; i += 1) {
    const obj = {
      id: `${i}`,
      case_number: 100,
      case_title: `Case Title ${i}`,
      process_name: `Process ${i}`,
      assigned: `Avatar ${i}`,
      current_task: `Task ${i}`,
      status: `badge ${i}`,
      started: `21/21/${i}`,
      due_date: `21/21/${i}`,
    };

    objectsList.push(obj);
  }

  return objectsList;
};

const dataTask = () => ({
  data: [
    {
      id: 6,
      element_name: "Form Task",
      user_id: 1,
      process_id: 2,
      due_at: "2024-09-23T19:30:36+00:00",
      process_request_id: 3,
      user_viewed_at: "2024-09-20 19:31:04",
      advanceStatus: "open",
      process: {
        id: 2,
        name: "hpa",
        has_timer_start_events: false,
        projects: "[]",
      },
      user: {
        id: 1,
        firstname: "Admin",
        lastname: "User",
        username: "admin",
        avatar: null,
        fullname: "Admin User",
      },
      can_view_parent_request: false,
    },
  ],
  meta: {
    filter: "",
    sort_by: "",
    sort_order: "",
    count: 1,
    total_pages: 1,
    in_overdue: 0,
    current_page: 1,
    from: 1,
    last_page: 1,
    links: [
      {
        url: null,
        label: "&laquo; Previous",
        active: false,
      },
      {
        url: "http:\/\/localhost:8092\/api\/1.0\/tasks-by-case?page=1",
        label: "1",
        active: true,
      },
      {
        url: null,
        label: "Next &raquo;",
        active: false,
      },
    ],
    path: "http:\/\/localhost:8092\/api\/1.0\/tasks-by-case",
    per_page: 10,
    to: 1,
    total: 1,
  },
});

export const getAllDataTask = async ({ page, perPage }) => {
  const response = [];

  for (let index = 0; index < perPage; index += 1) {
    const allData = dataTask();
    const item = allData.data[0];
    item.id = index + 100 * page;
    item.element_name = `${page} ${item.element_name}`;
    response.push(item);
  }

  return response;
};

export const getDataTask = async ({ params, pagination }) => {
  if (api) {
    const response = await api.get("tasks-by-case/", {
      params,
    });

    return response.data.data;
  }

  return getAllDataTask(pagination);
};
