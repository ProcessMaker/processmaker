const baseUrl = (__ENV.PM_K6_BASE_URL || '').replace(/\/$/, '');

export const config = {
  token: __ENV.PM_K6_TOKEN || '',
  endpoints: {
    startProcesses: `${baseUrl}/api/1.0/start_processes?page=1&per_page=15&filter=&order_by=category.name%2Cname&order_direction=asc%2Casc&include=events%2Ccategories&without_event_definitions=true`,
  },
};

export function validateConfiguration() {
  if (!baseUrl) {
    throw new Error('PM_K6_BASE_URL is required.');
  }

  const token = config.token.trim();
  if (!token || ['null', 'undefined', 'fake_jwt'].includes(token.toLowerCase())) {
    throw new Error('PM_K6_TOKEN must contain a valid bearer token.');
  }
}

export function getHeaders() {
  validateConfiguration();

  return {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${config.token}`,
  };
}
