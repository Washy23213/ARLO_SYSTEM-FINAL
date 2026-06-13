import { writeFileSync } from 'node:fs';

const width = 2400;
const height = 1500;

const colors = {
  text: '#0f172a',
  muted: '#475569',
  line: '#94a3b8',
  attrLine: '#94a3b8',
  entityStroke: '#15803d',
  entityFill: '#86efac',
  relationStroke: '#1d4ed8',
  relationFill: '#dbeafe',
  attributeStroke: '#ea580c',
  attributeFill: '#fed7aa',
};

const entities = {
  alumni: { label: 'ALUMNI', x: 220, y: 320, w: 180, h: 50 },
  superAdmin: { label: 'SUPER ADMIN', x: 1610, y: 255, w: 230, h: 50 },
  admin: { label: 'ADMIN', x: 2000, y: 320, w: 180, h: 50 },
  tracer: { label: 'TRACER RECORD', x: 220, y: 760, w: 210, h: 50 },
  submission: { label: 'JOB SUBMISSION', x: 675, y: 760, w: 220, h: 50 },
  application: { label: 'JOB APPLICATION', x: 1130, y: 760, w: 230, h: 50 },
  job: { label: 'JOB POSTING', x: 1590, y: 760, w: 215, h: 50 },
  feedback: { label: 'FEEDBACK', x: 1080, y: 1125, w: 230, h: 50 },
  announcement: { label: 'ANNOUNCEMENT', x: 1590, y: 1125, w: 250, h: 50 },
};

const attributes = [
  ['alumni', 'Alumni ID', 75, 235, 130],
  ['alumni', 'Email', 250, 220, 120],
  ['alumni', 'Name', 420, 260, 120],
  ['alumni', 'Program', 75, 405, 120],
  ['alumni', 'Year Graduated', 310, 405, 165],

  ['superAdmin', 'Super Admin ID', 1490, 185, 160],
  ['superAdmin', 'Email', 1665, 170, 120],
  ['superAdmin', 'Account Control', 1605, 400, 175],

  ['admin', 'Admin ID', 2025, 235, 130],
  ['admin', 'Email', 2170, 235, 120],
  ['admin', 'Name', 2210, 300, 120],
  ['admin', 'Access Role', 2025, 405, 145],

  ['tracer', 'Tracer ID', 115, 675, 130],
  ['tracer', 'Employment Status', 330, 675, 190],
  ['tracer', 'Company / Position', 85, 855, 190],
  ['tracer', 'Satisfaction', 345, 855, 140],

  ['submission', 'Submission ID', 610, 675, 160],
  ['submission', 'Job Details', 610, 850, 135],
  ['submission', 'Status', 850, 850, 120],

  ['application', 'Application ID', 1055, 675, 170],
  ['application', 'Resume', 1295, 675, 120],
  ['application', 'Status', 1190, 850, 120],

  ['job', 'Job ID', 1530, 675, 120],
  ['job', 'Title / Company', 1745, 675, 170],
  ['job', 'Active Status', 1535, 850, 150],
  ['job', 'Deadline', 1760, 850, 120],

  ['feedback', 'Feedback ID', 965, 1040, 150],
  ['feedback', 'Subject / Message', 1250, 1040, 190],
  ['feedback', 'Priority / Status', 1095, 1240, 175],

  ['announcement', 'Announcement ID', 1455, 1040, 190],
  ['announcement', 'Title / Content', 1710, 1040, 170],
  ['announcement', 'Audience', 1835, 1220, 120],
  ['announcement', 'Published Status', 1535, 1285, 175],
];

const relationships = [
  {
    label: 'HAS',
    cx: 310,
    cy: 555,
    lines: [
      [[310, 370], [310, 555]],
      [[310, 555], [310, 760]],
    ],
  },
  {
    label: 'PROPOSES',
    cx: 555,
    cy: 555,
    lines: [
      [[400, 330], [555, 330], [555, 555]],
      [[555, 555], [555, 705], [730, 760]],
    ],
  },
  {
    label: 'APPLIES',
    cx: 930,
    cy: 650,
    lines: [
      [[400, 360], [930, 360], [930, 650]],
      [[930, 650], [1195, 650], [1195, 760]],
    ],
  },
  {
    label: 'CRUD ADMIN',
    cx: 1890,
    cy: 345,
    lines: [
      [[1840, 280], [1890, 280], [1890, 345]],
      [[1890, 345], [2000, 345]],
    ],
  },
  {
    label: 'EDITS ACCOUNT',
    cx: 1180,
    cy: 305,
    lines: [
      [[1610, 280], [1330, 280], [1330, 305], [1180, 305]],
      [[1180, 305], [760, 305], [760, 345], [400, 345]],
    ],
  },
  {
    label: 'REVIEWS',
    cx: 1055,
    cy: 555,
    lines: [
      [[2000, 345], [1875, 345], [1875, 460], [1055, 460], [1055, 555]],
      [[1055, 555], [1055, 705], [785, 760]],
    ],
  },
  {
    label: 'REVIEWS',
    cx: 1450,
    cy: 555,
    lines: [
      [[2000, 355], [1900, 355], [1900, 500], [1450, 500], [1450, 555]],
      [[1450, 555], [1450, 705], [1245, 760]],
    ],
  },
  {
    label: 'RECEIVES',
    cx: 1475,
    cy: 785,
    lines: [
      [[1360, 785], [1475, 785]],
      [[1475, 785], [1590, 785]],
    ],
  },
  {
    label: 'POSTS',
    cx: 1845,
    cy: 555,
    lines: [
      [[2000, 370], [1935, 370], [1935, 555], [1845, 555]],
      [[1845, 555], [1845, 705], [1698, 760]],
    ],
  },
  {
    label: 'SENDS',
    cx: 850,
    cy: 1030,
    lines: [
      [[400, 370], [515, 370], [515, 1030], [850, 1030]],
      [[850, 1030], [850, 1150], [1080, 1150]],
    ],
  },
  {
    label: 'HANDLES',
    cx: 1450,
    cy: 1030,
    lines: [
      [[2065, 370], [2065, 990], [1450, 990], [1450, 1030]],
      [[1450, 1030], [1450, 1150], [1310, 1150]],
    ],
  },
  {
    label: 'PUBLISHES',
    cx: 1845,
    cy: 1030,
    lines: [
      [[2115, 370], [2115, 990], [1845, 990], [1845, 1030]],
      [[1845, 1030], [1845, 1150], [1715, 1150]],
    ],
  },
];

function esc(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function center(shape) {
  return { x: shape.x + shape.w / 2, y: shape.y + shape.h / 2 };
}

function pointsPath(points) {
  return points.map(([x, y], index) => `${index === 0 ? 'M' : 'L'} ${x} ${y}`).join(' ');
}

function entitySvg(entity) {
  return `
    <g>
      <rect x="${entity.x}" y="${entity.y}" width="${entity.w}" height="${entity.h}" fill="${colors.entityFill}" stroke="${colors.entityStroke}" stroke-width="2.5"/>
      <text x="${entity.x + entity.w / 2}" y="${entity.y + 32}" text-anchor="middle" font-size="17" font-weight="850" fill="${colors.text}">${esc(entity.label)}</text>
    </g>`;
}

function attributeSvg(attribute) {
  const [, label, x, y, w = 125] = attribute;
  return `
    <g>
      <ellipse cx="${x + w / 2}" cy="${y + 19}" rx="${w / 2}" ry="19" fill="${colors.attributeFill}" stroke="${colors.attributeStroke}" stroke-width="2"/>
      <text x="${x + w / 2}" y="${y + 24}" text-anchor="middle" font-size="12" font-weight="700" fill="${colors.text}">${esc(label)}</text>
    </g>`;
}

function attributeLineSvg(attribute) {
  const [entityKey, , x, y, w = 125] = attribute;
  const entityCenter = center(entities[entityKey]);
  return `<line x1="${entityCenter.x}" y1="${entityCenter.y}" x2="${x + w / 2}" y2="${y + 19}" stroke="${colors.attrLine}" stroke-width="1.2"/>`;
}

function diamondSvg(relationship) {
  const w = Math.max(62, Math.min(104, relationship.label.length * 5.8 + 24));
  const h = 34;
  return `
    <g>
      <polygon points="${relationship.cx},${relationship.cy - h} ${relationship.cx + w},${relationship.cy} ${relationship.cx},${relationship.cy + h} ${relationship.cx - w},${relationship.cy}" fill="${colors.relationFill}" stroke="${colors.relationStroke}" stroke-width="2.6"/>
      <text x="${relationship.cx}" y="${relationship.cy + 5}" text-anchor="middle" font-size="12.5" font-weight="850" fill="${colors.text}">${esc(relationship.label)}</text>
    </g>`;
}

function relationshipLineSvg(relationship) {
  return relationship.lines.map((points) => (
    `<path d="${pointsPath(points)}" fill="none" stroke="${colors.line}" stroke-width="1.2" stroke-opacity="0.7" stroke-linecap="round" stroke-linejoin="round"/>`
  )).join('\n');
}

function legendItem(x, y, shape, label) {
  if (shape === 'entity') {
    return `
      <rect x="${x}" y="${y - 17}" width="44" height="25" fill="${colors.entityFill}" stroke="${colors.entityStroke}" stroke-width="2"/>
      <text x="${x + 58}" y="${y}" font-size="15" fill="${colors.muted}">${esc(label)}</text>`;
  }

  if (shape === 'relationship') {
    return `
      <polygon points="${x + 22},${y - 22} ${x + 53},${y - 4} ${x + 22},${y + 14} ${x - 9},${y - 4}" fill="${colors.relationFill}" stroke="${colors.relationStroke}" stroke-width="2"/>
      <text x="${x + 72}" y="${y}" font-size="15" fill="${colors.muted}">${esc(label)}</text>`;
  }

  return `
    <ellipse cx="${x + 28}" cy="${y - 5}" rx="31" ry="17" fill="${colors.attributeFill}" stroke="${colors.attributeStroke}" stroke-width="2"/>
    <text x="${x + 72}" y="${y}" font-size="15" fill="${colors.muted}">${esc(label)}</text>`;
}

const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
  <defs>
    <style>
      text { font-family: "Segoe UI", Arial, sans-serif; letter-spacing: 0; }
    </style>
  </defs>

  <rect width="100%" height="100%" fill="#ffffff"/>

  <text x="1200" y="62" text-anchor="middle" font-size="38" font-weight="850" fill="${colors.text}">CMU ARLO Conceptual Data Model</text>
  <text x="1200" y="98" text-anchor="middle" font-size="19" fill="${colors.muted}">High-level data objects with separated Alumni, Admin, and Super Admin account control</text>

  <g>
    ${legendItem(170, 145, 'entity', 'Entity / main data object')}
    ${legendItem(720, 145, 'relationship', 'Relationship')}
    ${legendItem(1160, 145, 'attribute', 'Attribute')}
  </g>

  <g class="attribute-lines">
    ${attributes.map(attributeLineSvg).join('\n')}
  </g>

  <g class="relationship-lines">
    ${relationships.map(relationshipLineSvg).join('\n')}
  </g>

  <g class="relationships">
    ${relationships.map(diamondSvg).join('\n')}
  </g>

  <g class="entities">
    ${Object.values(entities).map(entitySvg).join('\n')}
  </g>

  <g class="attributes">
    ${attributes.map(attributeSvg).join('\n')}
  </g>

  <text x="1200" y="1365" text-anchor="middle" font-size="17" fill="${colors.muted}">This conceptual model focuses on the main ARLO data objects. Technical support tables such as sessions, notifications, super_admins, and logs are detailed in the ERD.</text>
</svg>
`;

writeFileSync('docs/arlo-data-model.svg', svg);
