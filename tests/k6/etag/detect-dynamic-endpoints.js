import http from 'k6/http';

export let options = {
  vus: 1,
  iterations: 5, // n iterations per URL.
};

const token = 'fake-jwt';
const endpoints = [
  'https://processmaker.test/api/1.0/requests?page=1&per_page=15&include=process%2Cparticipants%2CactiveTasks%2Cdata&pmql=%28requester%20%3D%20%22admin%22%29&filter=&order_by=id&order_direction=DESC&advanced_filter=%5B%7B%22subject%22%3A%7B%22type%22%3A%22Status%22%7D%2C%22operator%22%3A%22%3D%22%2C%22value%22%3A%22In%20Progress%22%7D%5D'
];

// Object to track ETag history for each endpoint.
const etagHistory = {};

// Limit to determine when an endpoint is considered dynamic.
const ETAG_HISTORY_LIMIT = 5;

export default function () {
  const headers = {
    Authorization: `Bearer ${token}`,
  };

  endpoints.forEach((url) => {
    const res1 = http.get(url, { headers });
    const etag = res1.headers['Etag'];

    // If no ETag is present, log a warning and skip further processing.
    if (!etag) {
      console.log(`No ETag found for ${url}`);
      return;
    }

    // Log the ETag value for debugging.
    console.log(`ETag: ${etag}`);

    // Initialize the ETag history for this endpoint if not already present.
    if (!etagHistory[url]) {
      etagHistory[url] = [];
    }

    // Add the ETag to the history if it is unique.
    if (!etagHistory[url].includes(etag)) {
      etagHistory[url].push(etag);
    }

    // Keep the history limited to the last N ETags.
    if (etagHistory[url].length > ETAG_HISTORY_LIMIT) {
      etagHistory[url].shift(); // Remove the oldest ETag
    }

    // Check if the endpoint is dynamic.
    // If the history is full and all ETags are unique, the endpoint is considered dynamic.
    if (
      etagHistory[url].length === ETAG_HISTORY_LIMIT &&
      new Set(etagHistory[url]).size === ETAG_HISTORY_LIMIT
    ) {
      console.log(`Dynamic endpoint detected: ${url}`);
    }
  });
}