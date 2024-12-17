import http from 'k6/http';
import { config, headers } from './config.js';

export let options = {
  vus: 1,
  iterations: 5, // n iterations per URL.
};

const endpoints = [
  config.endpoints.startProcesses,
];

// Object to track ETag history for each endpoint.
const etagHistory = {};

// Limit to determine when an endpoint is considered dynamic.
const ETAG_HISTORY_LIMIT = 5;

export default function () {
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