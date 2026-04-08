import http from 'k6/http';
import { check, sleep } from 'k6';

// export const options = {
//   vus: 50,
//   duration: '30s',
// };

export default function () {
  // 1. เข้าหน้า login (เอา CSRF + cookie)
  let res = http.get('https://smarthub.trainingzenter.com/');

  // 2. ดึง CSRF token
  let csrf = res.html().find('input[name=_token]').first().attr('value');

  // 3. login (k6 จะเก็บ cookie ให้อัตโนมัติ)
  let loginRes = http.post('https://smarthub.trainingzenter.com/', {
    _token: csrf,
    username: 'KK01012569',
    password: 'KK01012569',
  });

  check(loginRes, {
    'login success': (r) =>
      r.status === 302 || r.status === 200,
  });

  // 4. เข้า dashboard (ต้อง auth แล้ว)
  let dashboard = http.get('https://smarthub.trainingzenter.com/main');

  check(dashboard, {
    'dashboard loaded': (r) => r.status === 200,
  });

  sleep(1);
}
