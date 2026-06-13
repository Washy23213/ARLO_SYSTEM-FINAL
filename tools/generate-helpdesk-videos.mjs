import { spawn } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

const root = process.cwd();
const appUrl = (process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const edgePath = process.env.EDGE_PATH || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const outputDir = path.join(root, 'public', 'videos', 'help-desk');
const viewport = { width: 1280, height: 720 };
const demoEmail = 'helpdesk.walkthrough.video@cmu.edu.ph';
const demoPassword = 'HelpdeskGuide123!';
const demoMarker = 'HELPDESK-WALKTHROUGH-VIDEO';

const guides = [
  {
    output: 'getting-started.webm',
    title: 'Profile setup guide',
    subtitle: 'Complete the alumni profile before using the other portal modules.',
    scenes: [
      {
        path: '/dashboard',
        step: 'Step 1',
        label: 'Open Profile',
        caption: 'From the dashboard sidebar, click Profile when you need to check your alumni record.',
        target: { hrefContains: '/profile', text: 'Profile' },
        fallback: { x: 20, y: 260, width: 260, height: 56 },
      },
      {
        path: '/profile',
        step: 'Step 2',
        label: 'Check contact details',
        caption: 'Review your name, email, phone, college, program, batch, address, and skills.',
        target: { selector: '#profile-edit-form' },
        prepare: `
          document.querySelector('[name="phone"]')?.setAttribute('value', '09170000000');
          const skills = document.querySelector('[name="skills"]');
          if (skills) skills.value = 'Mentoring, alumni networking, job referrals';
        `,
        fallback: { x: 330, y: 250, width: 820, height: 360 },
      },
      {
        path: '/profile',
        step: 'Step 3',
        label: 'Save information',
        caption: 'After checking the details, click Save Information so OARL can use the latest record.',
        target: { selector: '[data-profile-save-button]', text: 'Save Information' },
        fallback: { x: 910, y: 580, width: 210, height: 56 },
      },
    ],
  },
  {
    output: 'tracer-and-proof.webm',
    title: 'Tracer and proof guide',
    subtitle: 'Update employment details and proof from the Tracer page.',
    scenes: [
      {
        path: '/dashboard',
        step: 'Step 1',
        label: 'Open Tracer',
        caption: 'Open Tracer after your profile is correct, especially when your employment changes.',
        target: { hrefContains: '/tracer', text: 'Tracer' },
        fallback: { x: 20, y: 315, width: 260, height: 56 },
      },
      {
        path: '/tracer#tracer-form',
        step: 'Step 2',
        label: 'Update employment',
        caption: 'Choose your employment type, then enter your position, company, income range, and industry.',
        target: { selector: '#tracer_employment_type' },
        prepare: `
          const position = document.querySelector('[name="current_position"]');
          const company = document.querySelector('[name="current_company"]');
          if (position) position.value = position.value || 'IT Support Specialist';
          if (company) company.value = company.value || 'CMU Partner Office';
        `,
        fallback: { x: 310, y: 310, width: 870, height: 300 },
      },
      {
        path: '/tracer#tracer-form',
        step: 'Step 3',
        label: 'Attach proof and save',
        caption: 'If the form asks for proof, use Employment Proof, then save the tracer record for review.',
        target: { selector: '[name="employment_proof"]' },
        fallback: { x: 310, y: 365, width: 870, height: 130 },
      },
    ],
  },
  {
    output: 'updates-and-support.webm',
    title: 'Updates and support guide',
    subtitle: 'Read OARL updates and send the right support request.',
    scenes: [
      {
        path: '/dashboard',
        step: 'Step 1',
        label: 'Open Notifications',
        caption: 'Open Notifications to read announcements, job post results, account notices, and direct OARL messages.',
        target: { hrefContains: '/notifications', text: 'Notifications' },
        fallback: { x: 1180, y: 24, width: 72, height: 72 },
      },
      {
        path: '/notifications',
        step: 'Step 2',
        label: 'Read the update',
        caption: 'Check each notification card so you know what OARL needs you to do next.',
        target: { selector: '.portal-message-preview, article, form button, main' },
        fallback: { x: 310, y: 240, width: 850, height: 330 },
      },
      {
        path: '/feedback',
        step: 'Step 3',
        label: 'Ask for help clearly',
        caption: 'Use Feedback, choose the closest category, and describe the problem with the page name and what happened.',
        target: { selector: '#feedbackSubject, #feedbackMessage, .feedback-category-card' },
        prepare: `
          const subject = document.querySelector('#feedbackSubject');
          const message = document.querySelector('#feedbackMessage');
          if (subject) subject.value = 'Need help updating my tracer proof';
          if (message) message.value = 'I uploaded my employment proof and need OARL to check the review status.';
        `,
        fallback: { x: 300, y: 315, width: 880, height: 300 },
      },
    ],
  },
];
const guideFilter = process.env.GUIDE || '';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

async function waitForJson(url, attempts = 60) {
  for (let attempt = 0; attempt < attempts; attempt += 1) {
    try {
      const response = await fetch(url);
      if (response.ok) {
        return await response.json();
      }
    } catch {
      // Edge may still be starting.
    }
    await sleep(250);
  }
  throw new Error(`Timed out waiting for ${url}`);
}

function runArtisanTinker(code) {
  return new Promise((resolve, reject) => {
    const child = spawn('php', ['artisan', 'tinker', '--execute', code], {
      cwd: root,
      stdio: ['ignore', 'pipe', 'pipe'],
    });
    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (chunk) => {
      stdout += chunk.toString();
    });
    child.stderr.on('data', (chunk) => {
      stderr += chunk.toString();
    });
    child.on('error', reject);
    child.on('close', (codeValue) => {
      if (codeValue !== 0) {
        reject(new Error(stderr.trim() || stdout.trim() || `php artisan tinker exited with ${codeValue}`));
        return;
      }
      resolve(stdout.trim());
    });
  });
}

async function setupDemoAccount() {
  const setupCode = `
    $email = ${JSON.stringify(demoEmail)};
    $marker = ${JSON.stringify(demoMarker)};
    \\App\\Models\\User::query()
      ->where('email', $email)
      ->where('alumni_idnum', $marker)
      ->delete();
    $user = \\App\\Models\\User::create([
      'firstname' => 'CMU',
      'lastname' => 'Alumni Guide',
      'email' => $email,
      'password' => ${JSON.stringify(demoPassword)},
      'alumni_idnum' => $marker,
      'phone' => '09170000000',
      'role' => \\App\\Models\\User::ROLE_ALUMNI,
      'is_admin' => false,
      'email_verified_at' => now(),
    ]);
    if (! \\Illuminate\\Support\\Facades\\Hash::check(${JSON.stringify(demoPassword)}, $user->password)) {
      throw new \\RuntimeException('Temporary walkthrough password could not be verified.');
    }
    echo $user->email;
  `;

  await runArtisanTinker(setupCode);
}

async function cleanupDemoAccount() {
  const cleanupCode = `
    $user = \\App\\Models\\User::query()
      ->where('email', ${JSON.stringify(demoEmail)})
      ->where('alumni_idnum', ${JSON.stringify(demoMarker)})
      ->first();
    if ($user) {
      \\App\\Models\\TracerRecord::query()->where('user_id', $user->id)->delete();
      $user->delete();
    }
  `;

  try {
    await runArtisanTinker(cleanupCode);
  } catch (error) {
    console.warn(`Unable to clean temporary walkthrough account: ${error.message}`);
  }
}

function makeCdpClient(webSocketUrl) {
  const socket = new WebSocket(webSocketUrl);
  let id = 0;
  const pending = new Map();

  socket.addEventListener('message', (event) => {
    const message = JSON.parse(event.data);
    if (!message.id || !pending.has(message.id)) {
      return;
    }

    const { resolve, reject } = pending.get(message.id);
    pending.delete(message.id);

    if (message.error) {
      reject(new Error(message.error.message || 'CDP error'));
      return;
    }

    resolve(message.result);
  });

  const opened = new Promise((resolve, reject) => {
    socket.addEventListener('open', resolve, { once: true });
    socket.addEventListener('error', reject, { once: true });
  });

  return {
    opened,
    send(method, params = {}) {
      id += 1;
      socket.send(JSON.stringify({ id, method, params }));
      return new Promise((resolve, reject) => {
        pending.set(id, { resolve, reject });
      });
    },
    close() {
      socket.close();
    },
  };
}

async function evaluate(cdp, expression, options = {}) {
  const result = await cdp.send('Runtime.evaluate', {
    expression,
    awaitPromise: options.awaitPromise ?? true,
    returnByValue: options.returnByValue ?? true,
    timeout: options.timeout ?? 30000,
  });

  if (result.exceptionDetails) {
    throw new Error(result.exceptionDetails.text || 'Runtime evaluation failed');
  }

  return result.result?.value;
}

async function waitForDocumentReady(cdp) {
  for (let attempt = 0; attempt < 80; attempt += 1) {
    const ready = await evaluate(cdp, 'document.readyState === "complete" || document.readyState === "interactive"');
    if (ready) {
      await evaluate(cdp, 'document.fonts ? document.fonts.ready.then(() => true) : true');
      await sleep(250);
      return;
    }
    await sleep(150);
  }
  throw new Error('Timed out waiting for page readiness.');
}

async function navigate(cdp, url) {
  await cdp.send('Page.navigate', { url });
  await waitForDocumentReady(cdp);
  await evaluate(cdp, 'window.scrollTo(0, 0); true');
  await sleep(250);
}

function storeSetCookies(cookieJar, setCookieHeaders) {
  for (const header of setCookieHeaders) {
    const [cookiePair, ...attributes] = header.split(';').map((part) => part.trim());
    const equalsIndex = cookiePair.indexOf('=');
    if (equalsIndex === -1) continue;
    const name = cookiePair.slice(0, equalsIndex);
    const value = cookiePair.slice(equalsIndex + 1);
    cookieJar.set(name, {
      name,
      value,
      httpOnly: attributes.some((attribute) => attribute.toLowerCase() === 'httponly'),
    });
  }
}

function cookieHeader(cookieJar) {
  return Array.from(cookieJar.values())
    .map((cookie) => `${cookie.name}=${cookie.value}`)
    .join('; ');
}

async function loginThroughServer() {
  const cookieJar = new Map();
  const loginResponse = await fetch(`${appUrl}/login`, { redirect: 'manual' });
  storeSetCookies(cookieJar, loginResponse.headers.getSetCookie());
  const loginHtml = await loginResponse.text();
  const token = loginHtml.match(/name="_token" value="([^"]+)"/)?.[1];
  if (!token) {
    throw new Error('Unable to read login CSRF token.');
  }

  const body = new URLSearchParams({
    _token: token,
    email: demoEmail,
    password: demoPassword,
  });
  const postResponse = await fetch(`${appUrl}/login`, {
    method: 'POST',
    redirect: 'manual',
    headers: {
      'content-type': 'application/x-www-form-urlencoded',
      cookie: cookieHeader(cookieJar),
    },
    body,
  });
  storeSetCookies(cookieJar, postResponse.headers.getSetCookie());
  const location = postResponse.headers.get('location') || '';
  if (postResponse.status !== 302 || !location.includes('/dashboard')) {
    const text = await postResponse.text();
    throw new Error(`Server login failed with status ${postResponse.status}. ${text.slice(0, 300)}`);
  }

  return Array.from(cookieJar.values());
}

async function login(cdp) {
  const cookies = await loginThroughServer();
  await cdp.send('Network.enable');
  for (const cookie of cookies) {
    await cdp.send('Network.setCookie', {
      name: cookie.name,
      value: cookie.value,
      url: appUrl,
      path: '/',
      httpOnly: cookie.httpOnly,
      secure: false,
      sameSite: 'Lax',
    });
  }

  await navigate(cdp, `${appUrl}/dashboard`);
  const currentUrl = await evaluate(cdp, 'window.location.href');
  if (!String(currentUrl).includes('/dashboard')) {
    const pageText = await evaluate(cdp, 'document.body.innerText.slice(0, 800)');
    throw new Error(`Walkthrough session failed. Current URL: ${currentUrl}. Page text: ${pageText}`);
  }
}

function targetExpression(target, shouldScroll = true) {
  return `(${function resolveTargetRect(config, scrollElement) {
    const visible = (element) => {
      if (!(element instanceof Element)) return false;
      const style = window.getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none'
        && style.visibility !== 'hidden'
        && rect.width > 2
        && rect.height > 2;
    };

    const normalize = (element) => {
      if (!element) return null;
      let target = element;
      if (config.closest) {
        target = element.closest(config.closest) || element;
      }
      if (target.tagName === 'INPUT' || target.tagName === 'SELECT' || target.tagName === 'TEXTAREA') {
        target = target.closest('label, div, section, article, form') || target;
      }
      return target;
    };

    const selectors = String(config.selector || '')
      .split(',')
      .map((selector) => selector.trim())
      .filter(Boolean);

    let element = null;
    for (const selector of selectors) {
      element = Array.from(document.querySelectorAll(selector)).find(visible);
      if (element) break;
    }

    if (!element && config.hrefContains) {
      const hrefNeedle = String(config.hrefContains);
      element = Array.from(document.querySelectorAll('a[href]'))
        .find((link) => visible(link) && link.href.includes(hrefNeedle));
    }

    if (!element && config.text) {
      const textNeedle = String(config.text).toLowerCase();
      element = Array.from(document.querySelectorAll('a, button, label, h1, h2, h3, p, span, div'))
        .find((node) => visible(node) && (node.textContent || '').trim().toLowerCase().includes(textNeedle));
    }

    const target = normalize(element);
    if (!target) return null;

    if (scrollElement) {
      target.scrollIntoView({ block: config.block || 'center', inline: 'center' });
    }

    const rect = target.getBoundingClientRect();
    return {
      x: Math.max(0, rect.x),
      y: Math.max(0, rect.y),
      width: Math.max(8, Math.min(window.innerWidth - Math.max(0, rect.x), rect.width)),
      height: Math.max(8, Math.min(window.innerHeight - Math.max(0, rect.y), rect.height)),
    };
  }.toString()})(${JSON.stringify(target || {})}, ${JSON.stringify(shouldScroll)})`;
}

async function captureScene(cdp, scene) {
  await navigate(cdp, `${appUrl}${scene.path}`);

  if (scene.prepare) {
    await evaluate(cdp, `(function () { ${scene.prepare}; return true; })()`);
    await sleep(150);
  }

  let rect = await evaluate(cdp, targetExpression(scene.target, true));
  await sleep(350);
  rect = await evaluate(cdp, targetExpression(scene.target, false)) || scene.fallback;

  const screenshot = await cdp.send('Page.captureScreenshot', {
    format: 'png',
    captureBeyondViewport: false,
  });

  const padded = {
    x: clamp(rect.x - 14, 0, viewport.width - 24),
    y: clamp(rect.y - 14, 0, viewport.height - 24),
    width: clamp(rect.width + 28, 24, viewport.width),
    height: clamp(rect.height + 28, 24, viewport.height),
  };

  if (padded.x + padded.width > viewport.width) {
    padded.width = viewport.width - padded.x;
  }
  if (padded.y + padded.height > viewport.height) {
    padded.height = viewport.height - padded.y;
  }

  return {
    ...scene,
    imageDataUrl: `data:image/png;base64,${screenshot.data}`,
    highlight: padded,
    hotspot: {
      x: clamp(padded.x + padded.width * 0.72, 28, viewport.width - 28),
      y: clamp(padded.y + padded.height * 0.58, 28, viewport.height - 84),
    },
  };
}

async function captureGuideScenes(cdp, guide) {
  const scenes = [];
  for (const scene of guide.scenes) {
    scenes.push(await captureScene(cdp, scene));
    console.log(`  Captured ${scene.label}`);
  }
  return { ...guide, scenes };
}

function buildRecordExpression(guide) {
  return `(${async function recordGuide(config) {
    const width = 1280;
    const height = 720;
    const fps = 24;
    const sceneDuration = 5.8;
    const outroDuration = 0.6;
    const durationSeconds = config.scenes.length * sceneDuration + outroDuration;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    document.body.style.margin = '0';
    document.body.style.background = '#07180e';
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    const images = await Promise.all(config.scenes.map(async (scene) => {
      const image = new Image();
      image.src = scene.imageDataUrl;
      await image.decode();
      return image;
    }));

    const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
    const lerp = (start, end, amount) => start + (end - start) * amount;
    const ease = (value) => {
      const t = clamp(value, 0, 1);
      return t * t * (3 - 2 * t);
    };

    const wrapText = (text, maxWidth) => {
      const words = String(text).split(' ');
      const lines = [];
      let line = '';
      for (const word of words) {
        const next = line ? `${line} ${word}` : word;
        if (ctx.measureText(next).width > maxWidth && line) {
          lines.push(line);
          line = word;
        } else {
          line = next;
        }
      }
      if (line) lines.push(line);
      return lines;
    };

    const roundedRect = (x, y, w, h, radius) => {
      const r = Math.min(radius, w / 2, h / 2);
      ctx.beginPath();
      ctx.moveTo(x + r, y);
      ctx.arcTo(x + w, y, x + w, y + h, r);
      ctx.arcTo(x + w, y + h, x, y + h, r);
      ctx.arcTo(x, y + h, x, y, r);
      ctx.arcTo(x, y, x + w, y, r);
      ctx.closePath();
    };

    const panel = (x, y, w, h, radius, fill, stroke = 'rgba(255,255,255,0.2)') => {
      roundedRect(x, y, w, h, radius);
      ctx.fillStyle = fill;
      ctx.fill();
      ctx.strokeStyle = stroke;
      ctx.lineWidth = 1;
      ctx.stroke();
    };

    const screenRect = { x: 24, y: 44, width: 930, height: 523 };
    const guideRect = { x: 978, y: 44, width: 278, height: 628 };

    const mapRectToScreen = (rect) => ({
      x: screenRect.x + rect.x * (screenRect.width / width),
      y: screenRect.y + rect.y * (screenRect.height / height),
      width: rect.width * (screenRect.width / width),
      height: rect.height * (screenRect.height / height),
    });

    const mapPointToScreen = (point) => ({
      x: screenRect.x + point.x * (screenRect.width / width),
      y: screenRect.y + point.y * (screenRect.height / height),
    });

    const drawScreenshot = (image, alpha = 1) => {
      ctx.save();
      ctx.globalAlpha = alpha;
      roundedRect(screenRect.x, screenRect.y, screenRect.width, screenRect.height, 18);
      ctx.clip();
      ctx.drawImage(image, screenRect.x, screenRect.y, screenRect.width, screenRect.height);
      ctx.restore();
    };

    const drawPointer = (x, y, scale, clickPulse) => {
      ctx.save();
      ctx.translate(x, y);
      ctx.scale(scale, scale);
      ctx.rotate(-0.14);

      if (clickPulse > 0) {
        ctx.strokeStyle = `rgba(245, 190, 63, ${0.85 - clickPulse * 0.6})`;
        ctx.lineWidth = 5;
        ctx.beginPath();
        ctx.arc(7, 20, 24 + clickPulse * 34, 0, Math.PI * 2);
        ctx.stroke();
      }

      ctx.fillStyle = 'rgba(7, 24, 14, 0.24)';
      ctx.beginPath();
      ctx.arc(13, 25, 18, 0, Math.PI * 2);
      ctx.fill();

      ctx.fillStyle = '#ffffff';
      ctx.strokeStyle = '#082915';
      ctx.lineWidth = 3.5;
      ctx.beginPath();
      ctx.moveTo(0, 0);
      ctx.lineTo(0, 50);
      ctx.lineTo(15, 38);
      ctx.lineTo(26, 63);
      ctx.lineTo(42, 56);
      ctx.lineTo(31, 32);
      ctx.lineTo(51, 32);
      ctx.closePath();
      ctx.fill();
      ctx.stroke();
      ctx.restore();
    };

    const drawHighlight = (rect, pulse) => {
      ctx.save();
      ctx.fillStyle = 'rgba(245, 190, 63, 0.12)';
      roundedRect(rect.x, rect.y, rect.width, rect.height, 18);
      ctx.fill();
      ctx.shadowColor = 'rgba(245, 190, 63, 0.45)';
      ctx.shadowBlur = 16 + pulse * 8;
      ctx.strokeStyle = '#f5be3f';
      ctx.lineWidth = 4;
      roundedRect(rect.x, rect.y, rect.width, rect.height, 18);
      ctx.stroke();
      ctx.restore();
    };

    const drawPortalFrame = (image) => {
      ctx.save();
      ctx.globalAlpha = 0.15;
      ctx.filter = 'blur(8px)';
      ctx.drawImage(image, -18, -10, width + 36, height + 20);
      ctx.restore();

      panel(
        screenRect.x - 8,
        screenRect.y - 8,
        screenRect.width + 16,
        screenRect.height + 16,
        24,
        'rgba(255,255,255,0.9)',
        'rgba(11,59,31,0.14)',
      );
      drawScreenshot(image);
      ctx.save();
      ctx.strokeStyle = 'rgba(11,59,31,0.22)';
      ctx.lineWidth = 2;
      roundedRect(screenRect.x, screenRect.y, screenRect.width, screenRect.height, 18);
      ctx.stroke();
      ctx.restore();
    };

    const drawGuidePanel = (scene, index, localProgress) => {
      panel(guideRect.x, guideRect.y, guideRect.width, guideRect.height, 26, 'rgba(255,255,255,0.96)', 'rgba(11,59,31,0.12)');

      panel(guideRect.x + 22, guideRect.y + 22, 214, 38, 19, '#0b3b1f', 'rgba(11,59,31,0.18)');
      ctx.fillStyle = '#f4e6bd';
      ctx.font = '900 12px Arial, sans-serif';
      ctx.fillText('REAL PORTAL GUIDE', guideRect.x + 44, guideRect.y + 47);

      ctx.fillStyle = '#0b3b1f';
      ctx.font = '900 28px Arial, sans-serif';
      const titleLines = wrapText(config.title, guideRect.width - 44);
      titleLines.slice(0, 3).forEach((line, lineIndex) => {
        ctx.fillText(line, guideRect.x + 22, guideRect.y + 102 + lineIndex * 32);
      });

      ctx.fillStyle = '#64748b';
      ctx.font = '700 15px Arial, sans-serif';
      const subtitleLines = wrapText(config.subtitle, guideRect.width - 44);
      subtitleLines.slice(0, 4).forEach((line, lineIndex) => {
        ctx.fillText(line, guideRect.x + 22, guideRect.y + 190 + lineIndex * 21);
      });

      const stepTop = guideRect.y + 280;
      config.scenes.forEach((_, stepIndex) => {
        const cx = guideRect.x + 42 + stepIndex * 52;
        const isActive = index === stepIndex;
        const isDone = stepIndex < index;
        panel(
          cx,
          stepTop,
          40,
          40,
          16,
          isActive ? '#0b3b1f' : (isDone ? '#dcfce7' : '#f1f5f9'),
          isActive ? 'rgba(11,59,31,0.18)' : 'rgba(15,23,42,0.08)',
        );
        ctx.fillStyle = isActive ? '#f4e6bd' : (isDone ? '#15803d' : '#64748b');
        ctx.font = '900 15px Arial, sans-serif';
        ctx.fillText(String(stepIndex + 1), cx + 16, stepTop + 26);
      });

      panel(guideRect.x + 22, guideRect.y + 344, 92, 42, 17, '#ecfdf5', 'rgba(16,185,129,0.16)');
      ctx.fillStyle = '#0b3b1f';
      ctx.font = '900 15px Arial, sans-serif';
      ctx.fillText(scene.step.toUpperCase(), guideRect.x + 40, guideRect.y + 371);

      ctx.fillStyle = '#07180e';
      ctx.font = '900 24px Arial, sans-serif';
      const labelLines = wrapText(scene.label, guideRect.width - 44);
      labelLines.slice(0, 2).forEach((line, lineIndex) => {
        ctx.fillText(line, guideRect.x + 22, guideRect.y + 420 + lineIndex * 29);
      });

      ctx.fillStyle = '#334155';
      ctx.font = '600 16px Arial, sans-serif';
      const captionLines = wrapText(scene.caption, guideRect.width - 44);
      captionLines.slice(0, 4).forEach((line, lineIndex) => {
        ctx.fillText(line, guideRect.x + 22, guideRect.y + 484 + lineIndex * 22);
      });

      const clickGlow = ease((localProgress - 0.43) / 0.2) * (1 - ease((localProgress - 0.7) / 0.2));
      panel(guideRect.x + 22, guideRect.y + 572, 142, 42, 17, clickGlow > 0.05 ? '#f5be3f' : '#ecfdf5', 'rgba(11,59,31,0.12)');
      ctx.fillStyle = '#0b3b1f';
      ctx.font = '900 15px Arial, sans-serif';
      ctx.fillText(clickGlow > 0.05 ? 'Click here' : 'Follow this', guideRect.x + 48, guideRect.y + 598);
    };

    const drawProgress = (progress) => {
      ctx.fillStyle = 'rgba(11,59,31,0.12)';
      roundedRect(24, 698, 1232, 8, 4);
      ctx.fill();
      const gradient = ctx.createLinearGradient(24, 698, 1256, 698);
      gradient.addColorStop(0, '#0b3b1f');
      gradient.addColorStop(0.55, '#22c55e');
      gradient.addColorStop(1, '#f5be3f');
      ctx.fillStyle = gradient;
      roundedRect(24, 698, Math.max(16, 1232 * progress), 8, 4);
      ctx.fill();
    };

    const drawFrame = (time) => {
      const globalProgress = clamp(time / durationSeconds, 0, 1);
      const rawIndex = Math.min(config.scenes.length - 1, Math.floor(time / sceneDuration));
      const localTime = time - rawIndex * sceneDuration;
      const localProgress = clamp(localTime / sceneDuration, 0, 1);
      const scene = config.scenes[rawIndex];
      const image = images[rawIndex];

      ctx.fillStyle = '#edf4ef';
      ctx.fillRect(0, 0, width, height);
      drawPortalFrame(image);

      if (rawIndex > 0 && localProgress < 0.16) {
        ctx.save();
        ctx.globalAlpha = 1 - ease(localProgress / 0.16);
        drawScreenshot(images[rawIndex - 1]);
        ctx.restore();
      }

      const pulse = Math.sin(time * Math.PI * 2) * 0.5 + 0.5;
      const screenHighlight = mapRectToScreen(scene.highlight);
      drawHighlight(screenHighlight, pulse);
      drawGuidePanel(scene, rawIndex, localProgress);

      const previousPoint = rawIndex > 0
        ? config.scenes[rawIndex - 1].hotspot
        : { x: 90, y: 620 };
      const previousScreenPoint = mapPointToScreen(previousPoint);
      const currentScreenPoint = mapPointToScreen(scene.hotspot);
      const start = {
        x: clamp(previousScreenPoint.x - 56, screenRect.x + 26, screenRect.x + screenRect.width - 64),
        y: clamp(previousScreenPoint.y + 50, screenRect.y + 26, screenRect.y + screenRect.height - 74),
      };
      const end = {
        x: clamp(currentScreenPoint.x, screenRect.x + 28, screenRect.x + screenRect.width - 28),
        y: clamp(currentScreenPoint.y, screenRect.y + 28, screenRect.y + screenRect.height - 28),
      };
      const move = ease((localProgress - 0.08) / 0.36);
      const clickPulse = ease((localProgress - 0.48) / 0.18) * (1 - ease((localProgress - 0.76) / 0.16));
      drawPointer(
        lerp(start.x, end.x, move),
        lerp(start.y, end.y, move),
        0.72 + clickPulse * 0.08,
        clickPulse,
      );

      drawProgress(globalProgress);
    };

    const mime = MediaRecorder.isTypeSupported('video/webm;codecs=vp9')
      ? 'video/webm;codecs=vp9'
      : 'video/webm';
    const stream = canvas.captureStream(fps);
    const recorder = new MediaRecorder(stream, { mimeType: mime, videoBitsPerSecond: 2200000 });
    const chunks = [];
    recorder.addEventListener('dataavailable', (event) => {
      if (event.data.size > 0) chunks.push(event.data);
    });
    const stopped = new Promise((resolve) => recorder.addEventListener('stop', resolve, { once: true }));
    recorder.start();

    const totalFrames = Math.ceil(durationSeconds * fps);
    for (let frame = 0; frame < totalFrames; frame += 1) {
      drawFrame(frame / fps);
      await new Promise((resolve) => setTimeout(resolve, 1000 / fps));
    }

    recorder.stop();
    await stopped;
    stream.getTracks().forEach((track) => track.stop());
    const blob = new Blob(chunks, { type: mime });
    const buffer = await blob.arrayBuffer();
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const chunkSize = 0x8000;
    for (let i = 0; i < bytes.length; i += chunkSize) {
      binary += String.fromCharCode(...bytes.subarray(i, i + chunkSize));
    }
    return btoa(binary);
  }.toString()})(${JSON.stringify(guide)})`;
}

async function main() {
  mkdirSync(outputDir, { recursive: true });
  await setupDemoAccount();

  const port = 9322 + Math.floor(Math.random() * 200);
  const userDataDir = path.join(tmpdir(), `arlo-helpdesk-video-${Date.now()}`);
  const edge = spawn(edgePath, [
    '--headless=new',
    '--disable-gpu',
    '--mute-audio',
    '--autoplay-policy=no-user-gesture-required',
    `--remote-debugging-port=${port}`,
    `--user-data-dir=${userDataDir}`,
    'about:blank',
  ], { stdio: 'ignore' });

  let cdp;
  try {
    const targets = await waitForJson(`http://127.0.0.1:${port}/json/list`);
    const pageTarget = targets.find((target) => target.type === 'page' && target.webSocketDebuggerUrl);
    if (!pageTarget) {
      throw new Error('Unable to find a debuggable Edge page target.');
    }
    cdp = makeCdpClient(pageTarget.webSocketDebuggerUrl);
    await cdp.opened;
    await cdp.send('Page.enable');
    await cdp.send('Runtime.enable');
    await cdp.send('Emulation.setDeviceMetricsOverride', {
      width: viewport.width,
      height: viewport.height,
      deviceScaleFactor: 1,
      mobile: false,
    });

    await login(cdp);

    const selectedGuides = guideFilter
      ? guides.filter((guide) => guide.output === guideFilter || guide.output.includes(guideFilter))
      : guides;
    if (selectedGuides.length === 0) {
      throw new Error(`No guide matched GUIDE=${guideFilter}`);
    }

    for (const guide of selectedGuides) {
      console.log(`Capturing real portal screens for ${guide.output}`);
      const capturedGuide = await captureGuideScenes(cdp, guide);

      await navigate(cdp, 'about:blank');
      console.log(`Recording ${guide.output}`);
      const result = await cdp.send('Runtime.evaluate', {
        expression: buildRecordExpression(capturedGuide),
        awaitPromise: true,
        returnByValue: true,
        timeout: 180000,
      });
      if (result.exceptionDetails) {
        throw new Error(result.exceptionDetails.text || 'Unable to render guide video');
      }
      const base64 = result.result?.value;
      if (!base64) {
        throw new Error(`No video data returned for ${guide.output}`);
      }
      writeFileSync(path.join(outputDir, guide.output), Buffer.from(base64, 'base64'));
      console.log(`Wrote public/videos/help-desk/${guide.output}`);
    }
  } finally {
    if (cdp) {
      cdp.close();
    }
    edge.kill();
    await sleep(750);
    try {
      rmSync(userDataDir, { recursive: true, force: true });
    } catch {
      // Edge can hold its profile lock briefly after exit; the temp profile is safe to leave.
    }
    await cleanupDemoAccount();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
