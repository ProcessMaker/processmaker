import { api } from "../variables/index";

export const updateCollection = async ({ collectionId, recordId, data }) => {
  const response = await api.put(`collections/${collectionId}/records/${recordId}`, data);

  return response.data;
};

export default {
  updateCollection,
};
