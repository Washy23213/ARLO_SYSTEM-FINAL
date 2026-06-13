import { writeFileSync } from 'node:fs';

const width = 2400;
const height = 1500;

const colors = {
  text: '#0f172a',
  muted: '#475569',
  grid: '#e2e8f0',
  user: '#0369a1',
  userFill: '#e0f2fe',
  admin: '#047857',
  adminFill: '#ecfdf5',
  activity: '#334155',
  activityFill: '#f8fafc',
  log: '#c2410c',
  logFill: '#fff7ed',
  labelFill: '#ffffff',
};

const entities = {
  users: {
    title: 'USERS',
    color: colors.user,
    fill: colors.userFill,
    x: 80,
    y: 150,
    w: 360,
    rows: [
      ['PK', 'id'],
      ['UK', 'email'],
      ['', 'firstname, lastname'],
      ['', 'department, degree'],
      ['', 'year_graduated'],
      ['', 'profile and tracer summary'],
    ],
  },
  admins: {
    title: 'ADMINS',
    color: colors.admin,
    fill: colors.adminFill,
    x: 1960,
    y: 150,
    w: 360,
    rows: [
      ['PK', 'id'],
      ['UK', 'email'],
      ['', 'firstname, lastname'],
      ['', 'assigned admin role'],
      ['', 'is_active'],
    ],
  },
  superAdmins: {
    title: 'SUPER_ADMINS',
    color: colors.admin,
    fill: colors.adminFill,
    x: 1460,
    y: 150,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['UK', 'email'],
      ['', 'firstname, lastname'],
      ['', 'account control authority'],
      ['', 'email_verified_at'],
    ],
  },
  tracer: {
    title: 'TRACER_RECORDS',
    color: colors.activity,
    fill: colors.activityFill,
    x: 520,
    y: 120,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'user_id'],
      ['', 'tracer_date'],
      ['', 'employment status'],
      ['', 'job satisfaction'],
    ],
  },
  jobSubmissions: {
    title: 'JOB_SUBMISSIONS',
    color: colors.activity,
    fill: colors.activityFill,
    x: 520,
    y: 500,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'user_id'],
      ['FK', 'reviewed_by'],
      ['', 'title, company'],
      ['', 'status'],
    ],
  },
  applications: {
    title: 'JOB_APPLICATIONS',
    color: colors.activity,
    fill: colors.activityFill,
    x: 980,
    y: 500,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'job_id'],
      ['FK', 'user_id'],
      ['FK', 'reviewed_by'],
      ['', 'status'],
    ],
  },
  feedback: {
    title: 'FEEDBACK',
    color: colors.activity,
    fill: colors.activityFill,
    x: 980,
    y: 850,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'user_id'],
      ['FK', 'assigned_to'],
      ['', 'subject'],
      ['', 'status, priority'],
    ],
  },
  sessions: {
    title: 'SESSIONS',
    color: colors.user,
    fill: colors.userFill,
    x: 80,
    y: 530,
    w: 360,
    rows: [
      ['PK', 'id'],
      ['FK', 'user_id'],
      ['', 'ip_address'],
      ['', 'last_activity'],
    ],
  },
  notifications: {
    title: 'NOTIFICATIONS',
    color: colors.user,
    fill: colors.userFill,
    x: 80,
    y: 820,
    w: 360,
    rows: [
      ['PK', 'id'],
      ['FK', 'notifiable_id'],
      ['', 'type'],
      ['', 'data'],
      ['', 'read_at'],
    ],
  },
  jobs: {
    title: 'JOBS',
    color: colors.admin,
    fill: colors.adminFill,
    x: 1460,
    y: 500,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'created_by'],
      ['FK', 'archived_by'],
      ['', 'title, company'],
      ['', 'is_active'],
    ],
  },
  announcements: {
    title: 'ANNOUNCEMENTS',
    color: colors.admin,
    fill: colors.adminFill,
    x: 1460,
    y: 850,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'created_by'],
      ['FK', 'archived_by'],
      ['', 'title, content'],
      ['', 'is_published'],
    ],
  },
  logs: {
    title: 'SYSTEM_LOGS',
    color: colors.log,
    fill: colors.logFill,
    x: 980,
    y: 1190,
    w: 380,
    rows: [
      ['PK', 'id'],
      ['FK', 'user_id'],
      ['FK', 'admin_id'],
      ['FK', 'super_admin_id'],
      ['', 'action'],
      ['', 'created_at'],
    ],
  },
};

const headerH = 54;
const rowH = 36;

function h(entity) {
  return headerH + entity.rows.length * rowH;
}

function esc(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function entitySvg(entity) {
  const totalH = h(entity);
  const keyW = 70;
  const r = 10;
  let out = `
    <g class="entity">
      <rect x="${entity.x}" y="${entity.y}" width="${entity.w}" height="${totalH}" rx="${r}" fill="${entity.fill}" stroke="${entity.color}" stroke-width="3"/>
      <rect x="${entity.x}" y="${entity.y}" width="${entity.w}" height="${headerH}" rx="${r}" fill="${entity.color}" opacity="0.12"/>
      <line x1="${entity.x}" y1="${entity.y + headerH}" x2="${entity.x + entity.w}" y2="${entity.y + headerH}" stroke="${entity.color}" stroke-width="2"/>
      <text x="${entity.x + entity.w / 2}" y="${entity.y + 35}" text-anchor="middle" font-size="24" font-weight="700" fill="${colors.text}">${esc(entity.title)}</text>`;

  entity.rows.forEach(([key, value], index) => {
    const y = entity.y + headerH + index * rowH;
    if (index > 0) {
      out += `<line x1="${entity.x}" y1="${y}" x2="${entity.x + entity.w}" y2="${y}" stroke="${entity.color}" stroke-width="1" opacity="0.35"/>`;
    }
    out += `<line x1="${entity.x + keyW}" y1="${y}" x2="${entity.x + keyW}" y2="${y + rowH}" stroke="${entity.color}" stroke-width="1" opacity="0.35"/>`;
    out += `<text x="${entity.x + 34}" y="${y + 24}" text-anchor="middle" font-size="16" font-weight="700" fill="${entity.color}">${esc(key)}</text>`;
    out += `<text x="${entity.x + keyW + 16}" y="${y + 24}" font-size="17" fill="${colors.text}">${esc(value)}</text>`;
  });

  out += '</g>';
  return out;
}

function pointsPath(points) {
  return points.map(([x, y], index) => `${index === 0 ? 'M' : 'L'} ${x} ${y}`).join(' ');
}

function edge(points, label, labelX, labelY, options = {}) {
  const stroke = options.stroke ?? colors.muted;
  const marker = options.marker ?? 'arrow';
  const dash = options.dashed ? 'stroke-dasharray="8 7"' : '';
  const labelLines = label.split('\n');
  const labelW = Math.max(64, Math.max(...labelLines.map((line) => line.length)) * 7 + 20);
  const labelH = 24 + (labelLines.length - 1) * 18;
  const text = labelLines.map((line, index) => (
    `<text x="${labelX}" y="${labelY + index * 18}" text-anchor="middle" font-size="15" font-weight="700" fill="${stroke}">${esc(line)}</text>`
  )).join('');

  return `
    <path d="${pointsPath(points)}" fill="none" stroke="${stroke}" stroke-width="2.2" ${dash} marker-end="url(#${marker})"/>
    <g>
      <rect x="${labelX - labelW / 2}" y="${labelY - 18}" width="${labelW}" height="${labelH + 8}" rx="7" fill="${colors.labelFill}" stroke="${colors.grid}"/>
      ${text}
    </g>`;
}

function legendItem(x, y, color, fill, label) {
  return `
    <rect x="${x}" y="${y - 16}" width="24" height="16" rx="4" fill="${fill}" stroke="${color}" stroke-width="2"/>
    <text x="${x + 34}" y="${y - 3}" font-size="16" fill="${colors.muted}">${esc(label)}</text>`;
}

function note(x, y, w, text) {
  return `
    <g>
      <rect x="${x}" y="${y}" width="${w}" height="34" rx="8" fill="#ffffff" stroke="${colors.grid}" stroke-width="1.5"/>
      <text x="${x + w / 2}" y="${y + 22}" text-anchor="middle" font-size="15" font-weight="700" fill="${colors.log}">${esc(text)}</text>
    </g>`;
}

const e = entities;
const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <defs>
    <marker id="arrow" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto" markerUnits="strokeWidth">
      <path d="M 0 0 L 12 6 L 0 12 z" fill="${colors.muted}"/>
    </marker>
    <marker id="arrowUser" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto" markerUnits="strokeWidth">
      <path d="M 0 0 L 12 6 L 0 12 z" fill="${colors.user}"/>
    </marker>
    <marker id="arrowAdmin" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto" markerUnits="strokeWidth">
      <path d="M 0 0 L 12 6 L 0 12 z" fill="${colors.admin}"/>
    </marker>
    <marker id="arrowLog" markerWidth="12" markerHeight="12" refX="10" refY="6" orient="auto" markerUnits="strokeWidth">
      <path d="M 0 0 L 12 6 L 0 12 z" fill="${colors.log}"/>
    </marker>
    <style>
      text { font-family: "Segoe UI", Arial, sans-serif; }
      .title { font-weight: 800; letter-spacing: 0; }
      .subtitle { fill: ${colors.muted}; }
      .zone-title { font-size: 18px; font-weight: 800; fill: ${colors.muted}; text-transform: uppercase; letter-spacing: 1px; }
    </style>
  </defs>

  <rect width="100%" height="100%" fill="#ffffff"/>
  <text x="1200" y="58" text-anchor="middle" class="title" font-size="34" fill="${colors.text}">CMU ARLO Entity Relationship Diagram</text>
  <text x="1200" y="90" text-anchor="middle" class="subtitle" font-size="18">Professional view showing separated USERS, ADMINS, and SUPER_ADMINS account tables</text>

  <g>
    ${legendItem(90, 110, colors.user, colors.userFill, 'Alumni-owned accounts and records')}
    ${legendItem(460, 110, colors.activity, colors.activityFill, 'Operational records')}
    ${legendItem(760, 110, colors.admin, colors.adminFill, 'Admin and super admin account control')}
    ${legendItem(1120, 110, colors.log, colors.logFill, 'Audit trail')}
  </g>

  <g class="edges">
    ${edge([[440, 250], [520, 250]], '1:M\nhas', 480, 235, { stroke: colors.user, marker: 'arrowUser' })}
    ${edge([[260, 420], [260, 530]], '1:M\nowns', 320, 480, { stroke: colors.user, marker: 'arrowUser' })}
    ${edge([[80, 310], [38, 310], [38, 930], [80, 930]], '1:M\nreceives', 110, 775, { stroke: colors.user, marker: 'arrowUser' })}
    ${edge([[440, 335], [480, 335], [480, 620], [520, 620]], '1:M\nproposes', 500, 470, { stroke: colors.user, marker: 'arrowUser' })}
    ${edge([[440, 365], [470, 365], [470, 780], [940, 780], [940, 620], [980, 620]], '1:M\napplies', 710, 765, { stroke: colors.user, marker: 'arrowUser' })}
    ${edge([[440, 395], [465, 395], [465, 980], [980, 980]], '1:M\nsubmits', 710, 965, { stroke: colors.user, marker: 'arrowUser' })}

    ${edge([[1460, 275], [1340, 275], [1340, 125], [260, 125], [260, 150]], '1:M\nedits accounts', 1230, 165, { stroke: colors.admin, marker: 'arrowAdmin' })}
    ${edge([[1840, 250], [1960, 250]], '1:M\nCRUD admins', 1900, 235, { stroke: colors.admin, marker: 'arrowAdmin' })}

    ${edge([[1960, 290], [1900, 290], [1900, 430], [920, 430], [920, 620], [900, 620]], '1:M\nreviews', 1390, 415, { stroke: colors.admin, marker: 'arrowAdmin' })}
    ${edge([[1960, 315], [1915, 315], [1915, 455], [1380, 455], [1380, 620], [1360, 620]], '1:M\nreviews', 1590, 440, { stroke: colors.admin, marker: 'arrowAdmin' })}
    ${edge([[1960, 340], [1900, 340], [1900, 800], [1380, 800], [1380, 980], [1360, 980]], '1:M\nhandles', 1590, 785, { stroke: colors.admin, marker: 'arrowAdmin' })}
    ${edge([[1960, 360], [1885, 360], [1885, 620], [1840, 620]], '1:M\ncreates', 1908, 580, { stroke: colors.admin, marker: 'arrowAdmin' })}
    ${edge([[1960, 380], [1870, 380], [1870, 970], [1840, 970]], '1:M\npublishes', 1908, 905, { stroke: colors.admin, marker: 'arrowAdmin' })}
    ${edge([[1460, 630], [1360, 630]], '1:M\nreceives', 1410, 610, { stroke: colors.activity })}
    ${edge([[1650, 384], [1650, 1160], [1360, 1280]], '1:M\naudit log', 1535, 1120, { stroke: colors.log, marker: 'arrowLog', dashed: true })}
  </g>

  ${note(985, 1135, 370, 'Audit refs: user_id, admin_id, super_admin_id')}
  ${note(1460, 405, 390, 'Super admin is separate from admins')}

  ${Object.values(entities).map(entitySvg).join('\n')}
</svg>
`;

writeFileSync('docs/arlo-erd-professional.svg', svg);
