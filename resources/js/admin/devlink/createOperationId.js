const createOperationId = () => {
  if (typeof window.crypto?.randomUUID === "function") {
    return window.crypto.randomUUID();
  }

  return `${Date.now()}-${Math.random().toString(36).slice(2)}`;
};

export default createOperationId;
