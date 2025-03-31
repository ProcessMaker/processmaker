import { getApi } from "../variables/index";

export const getReassignUsers = async (filter = null, taskId = null) => {
  const api = getApi();
  const response = await api.get("users_task_count", { params: { filter, assignable_for_task_id: taskId } });
  return response.data;
};

export const updateReassignUser = async (taskId, userId, comments = null) => {
  const api = getApi();
  const response = await api.put(`tasks/${taskId}`, { user_id: userId, comments });
  return response.data;
};

export const updateComment = async ({
  body,
  subject,
  commentableId,
  commentableType,
  parentId = 0,
  type = "COMMENT",
}) => {
  const api = getApi();
  const response = await api.post("comments/comments", {
    body,
    subject,
    commentable_id: commentableId,
    commentable_type: commentableType,
    type,
    parent_id: parentId,
  });
  return response.data;
};
