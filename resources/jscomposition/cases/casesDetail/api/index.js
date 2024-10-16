import { api } from "../variables";

export const getData = async () => {
  const objectsList = [];

  for (let i = 0; i <= 1; i += 1) {
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

export const getDataRequests = async (params) => {
  const response = await api.get("requests-by-case", params);

  return response.data;
};

export const getDataTask = async (params) => {
  const response = await api.get("tasks-by-case", params);
  return response.data;
};

export const getScreenData = (id) => {
  const response = ProcessMaker.apiClient.get(`/api/1.1/tasks/${id}/screen`);

  return response;
};

export const getUserConfiguration = async () => {
  const response = await api.get("users/configuration");

  return response.data;
};

export const updateUserConfiguration = async (data) => {
  const response = await api.put("users/configuration", data);

  return response.data;
};

export const getCommentsData = (params) => {
  const response = ProcessMaker.apiClient.get("comments-by-case", params);

  return response;
};
