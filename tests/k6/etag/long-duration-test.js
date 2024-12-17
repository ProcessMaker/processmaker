import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';
import { config, headers } from './config.js';

// Metrics to track response durations.
let duration200 = new Trend('duration_200');
let duration304 = new Trend('duration_304');

export let options = {
  stages: [
    { duration: '5m', target: 50 }, // Hold 50 VUs for 5 minutes.
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'], // 95% of requests should respond in < 1s.
    duration_200: ['avg<800'], // Ensure 200 OK average duration is < 800ms.
    duration_304: ['avg<400'], // Ensure 304 Not Modified avg duration is < 400ms.
  },
};

export default function () {
  const url = config.endpoints.startProcesses;
  let res = http.get(url, { headers });
  duration200.add(res.timings.duration);
  check(res, {
    'status is 200': (r) => r.status === 200,
    'ETag exists': (r) => !!r.headers.Etag,
  });

  const etag = res.headers.Etag;
  if (etag) {
    const conditionalHeaders = { ...headers, 'If-None-Match': etag };

    let conditionalRes = http.get(url, { headers: conditionalHeaders });
    duration304.add(conditionalRes.timings.duration);

    check(conditionalRes, {
      'status is 304': (r) => r.status === 304,
    });
  }

  sleep(1);
}