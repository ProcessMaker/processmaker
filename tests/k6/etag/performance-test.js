import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend } from 'k6/metrics';
import { config, headers } from './config.js';

// Custom metrics to track request durations.
let duration200 = new Trend('duration_200');
let duration304 = new Trend('duration_304');

export let options = {
  stages: [
    { duration: '10s', target: 10 },
    { duration: '20s', target: 50 },
    { duration: '10s', target: 0 },
  ],
};

export default function () {
  const baseUrl = config.endpoints.startProcesses;
  group('ETag Performance', () => {
    // Add the duration of the 200 response to the custom metric.
    let res = http.get(baseUrl, { headers });
    duration200.add(res.timings.duration);
    check(res, {
      'status is 200': (r) => r.status === 200,
    });

    // Use ETag with If-None-Match header to validate 304 response.
    const etag = res.headers.Etag;
    if (etag) {
      const conditionalHeaders = {
        ...headers,
        'If-None-Match': etag,
      };

      // Add the duration of the 304 response to the custom metric
      let conditionalRes = http.get(baseUrl, { headers: conditionalHeaders });
      duration304.add(conditionalRes.timings.duration);
      check(conditionalRes, {
        'status is 304': (r) => r.status === 304,
      });
    }

    sleep(1);
  });
}