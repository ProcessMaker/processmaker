import { getApi } from "../variables/index";

/**
 * Get reassign users using POST with form_data (for rule expression evaluation)
 * This replaces the obsolete GET method with the advanced POST logic from reassignMixin
 * 
 * @param {string|null} filter - Filter string to search users
 * @param {number|null} taskId - Task ID to get assignable users for
 * @param {Object|null} formData - Form data needed to calculate rule expressions
 * @param {number|null} currentTaskUserId - User ID to exclude from results (matches: task?.user_id ?? task?.user?.id)
 * @returns {Promise<Object>} Response data with users array
 */
export const getReassignUsers = async (
  filter = null,
  taskId = null,
  formData = null,
  currentTaskUserId = null
) => {
  const api = getApi();
  const params = {};
  
  if (filter) {
    params.filter = filter;
  }
  
  if (taskId) {
    params.assignable_for_task_id = taskId;
    
    // The variables are needed to calculate the rule expression.
    if (formData) {
      params.form_data = { ...formData };
      // Remove internal variables
      delete params.form_data._user;
      delete params.form_data._request;
      delete params.form_data._process;
    }
  }

  const response = await api.post("users_task_count", params);
  const data = response.data;
  
  // Filter out current user to prevent self-reassignment (matches mixin logic)
  if (currentTaskUserId && Array.isArray(data?.data)) {
    data.data = data.data.filter((user) => user.id !== currentTaskUserId);
  }
  
  return data;
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

export const updateCollection = async ({ collectionId, recordId, data }) => {
  const api = getApi();
  const response = await api.put(`collections/${collectionId}/records/${recordId}`, data);

  return response.data;
};
