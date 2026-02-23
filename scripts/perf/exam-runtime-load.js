import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8080';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || '';
const SESSION_ID = __ENV.EXAM_SESSION_ID || '';
const SESSION_TOKEN = __ENV.EXAM_SESSION_TOKEN || '';

export const options = {
  scenarios: {
    autosave_flow: {
      executor: 'constant-vus',
      vus: Number(__ENV.AUTOSAVE_VUS || 100),
      duration: __ENV.AUTOSAVE_DURATION || '3m',
      exec: 'autosaveFlow',
    },
    proctoring_flow: {
      executor: 'constant-vus',
      vus: Number(__ENV.PROCTORING_VUS || 50),
      duration: __ENV.PROCTORING_DURATION || '3m',
      exec: 'proctoringFlow',
    },
    resume_flow: {
      executor: 'constant-vus',
      vus: Number(__ENV.RESUME_VUS || 25),
      duration: __ENV.RESUME_DURATION || '3m',
      exec: 'resumeFlow',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1000'],
  },
};

function authHeaders() {
  const headers = { 'Content-Type': 'application/json' };

  if (AUTH_TOKEN) {
    headers.Authorization = `Bearer ${AUTH_TOKEN}`;
  }

  return headers;
}

export function autosaveFlow() {
  const payload = JSON.stringify({
    session_id: SESSION_ID,
    token: SESSION_TOKEN,
    last_question_id: 'q-1',
    draft_payload: { answer: 'A' },
    client_timestamp: new Date().toISOString(),
  });

  const response = http.post(`${BASE_URL}/api/exam/autosave`, payload, { headers: authHeaders() });

  check(response, {
    'autosave status is success': (r) => r.status === 200,
  });

  sleep(1);
}

export function proctoringFlow() {
  const heartbeatPayload = JSON.stringify({
    token: SESSION_TOKEN,
  });

  const eventPayload = JSON.stringify({
    token: SESSION_TOKEN,
    event_type: 'tab_blur',
    payload: { source: 'load-test' },
    severity: 'warning',
  });

  const heartbeat = http.post(`${BASE_URL}/api/proctoring/heartbeat`, heartbeatPayload, { headers: authHeaders() });
  const event = http.post(`${BASE_URL}/api/proctoring/event`, eventPayload, { headers: authHeaders() });

  check(heartbeat, {
    'heartbeat status is success': (r) => r.status === 200,
  });

  check(event, {
    'event status is success': (r) => r.status === 200,
  });

  sleep(1);
}

export function resumeFlow() {
  const response = http.get(
    `${BASE_URL}/api/exam/resume-state?session_id=${encodeURIComponent(SESSION_ID)}&token=${encodeURIComponent(SESSION_TOKEN)}`,
    { headers: authHeaders() }
  );

  check(response, {
    'resume status is success': (r) => r.status === 200,
  });

  sleep(1);
}
