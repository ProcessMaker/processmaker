export const config = {
  token: 'FAKE_JWT', // Replace with a valid token.
  endpoints: {
    startProcesses: 'https://processmaker.test/api/1.0/start_processes?page=1&per_page=15&filter=&order_by=category.name%2Cname&order_direction=asc%2Casc&include=events%2Ccategories&without_event_definitions=true',
  },
};

export const headers = {
  'Content-Type': 'application/json',
  Authorization: `Bearer ${config.token}`,
};