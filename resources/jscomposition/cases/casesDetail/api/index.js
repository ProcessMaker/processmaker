import { api } from "../variables";

const getData = async () => {
  const objectsList = [];

  for (let i = 0; i <= 31; i += 1) {
    const obj = {
      id: `${i}`,
      case_number: 100,
      element_name: `Case Title ${i}`,
      process: {
        name: `Process ${i}`,
      },
      user: {
        fullname: `Avatar ${i}`,
      },
      current_task: `Task ${i}`,
      status: "IN_PROGRESS",
      started: `21/21/${i}`,
      due_at: `21/21/${i}`,
      completed_date: `21/21/${i}`,
      screen_id: 4,
    };

    objectsList.push(obj);
  }

  return objectsList;
};

const getDataTask = async ({ params, pagination }) => {
  const response = await api.get("tasks-by-case/", {
    params: {
      ...params,
      ...pagination,
    },
  });

  return response.data.data;
};
const getScreenData = (id) => {
  const response = ProcessMaker.apiClient.get(`screens/${id}`);

  return response;
};

export { getData, getDataTask, getScreenData };
