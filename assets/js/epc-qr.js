// Phase 1B — QR code SEPA/EPC local, sans service externe.
// Le QR est généré dans le navigateur : aucune donnée de paiement n'est envoyée à un tiers.

const QR_VERSION = 6;
const QR_SIZE = QR_VERSION * 4 + 17;
const DATA_CODEWORDS = 136;
const ECC_CODEWORDS_PER_BLOCK = 18;
const BLOCKS = 2;
const DATA_CODEWORDS_PER_BLOCK = 68;
const FORMAT_XOR_MASK = 0x5412;
const FORMAT_GENERATOR = 0x537;

function esc(value){
  return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'}[c]));
}

function normalizeText(value, max = 70){
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^A-Za-z0-9 .,'+\-\/]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, max);
}

function normalizeIban(value){
  return String(value || '').replace(/\s+/g, '').toUpperCase();
}

function normalizeAmount(value){
  const normalized = String(value || '0').replace(',', '.').replace(/[^0-9.]/g, '');
  const n = Number.parseFloat(normalized || '0');
  if (!Number.isFinite(n) || n <= 0) return 'EUR0.00';
  return `EUR${n.toFixed(2)}`;
}

export function buildEpcQrPayload({ beneficiary, iban, bic, amount, communication }){
  const cleanBeneficiary = normalizeText(beneficiary || 'Equilibre Vital asbl', 70) || 'Equilibre Vital asbl';
  const cleanIban = normalizeIban(iban);
  const cleanBic = normalizeText(bic || '', 11).replace(/\s+/g, '').toUpperCase();
  const cleanCommunication = normalizeText(communication || '', 80);

  // Format EPC069-12 : chaque ligne a un rôle précis.
  // BCD / version / encodage UTF-8 / SEPA Credit Transfer / BIC / Nom / IBAN / Montant / ... / Communication.
  return [
    'BCD',
    '002',
    '1',
    'SCT',
    cleanBic,
    cleanBeneficiary,
    cleanIban,
    normalizeAmount(amount),
    '',
    '',
    cleanCommunication,
    ''
  ].join('\n');
}

function toUtf8Bytes(text){
  return Array.from(new TextEncoder().encode(text));
}

function appendBits(bits, value, length){
  for (let i = length - 1; i >= 0; i--) bits.push((value >>> i) & 1);
}

function packBitsToCodewords(bits){
  const out = [];
  for (let i = 0; i < bits.length; i += 8) {
    let byte = 0;
    for (let j = 0; j < 8; j++) byte = (byte << 1) | (bits[i + j] || 0);
    out.push(byte);
  }
  return out;
}

function createDataCodewords(text){
  const bytes = toUtf8Bytes(text);
  const maxBytes = 134;
  if (bytes.length > maxBytes) {
    throw new Error(`QR SEPA trop long (${bytes.length} octets, maximum ${maxBytes}).`);
  }

  const bits = [];
  appendBits(bits, 0b0100, 4);      // mode byte
  appendBits(bits, bytes.length, 8); // compteur byte pour versions 1 à 9
  bytes.forEach(byte => appendBits(bits, byte, 8));

  const capacityBits = DATA_CODEWORDS * 8;
  const terminator = Math.min(4, capacityBits - bits.length);
  appendBits(bits, 0, terminator);
  while (bits.length % 8 !== 0) bits.push(0);

  const data = packBitsToCodewords(bits);
  const pads = [0xEC, 0x11];
  let padIndex = 0;
  while (data.length < DATA_CODEWORDS) data.push(pads[padIndex++ % 2]);
  return data;
}

function initGf(){
  const exp = new Array(512).fill(0);
  const log = new Array(256).fill(0);
  let x = 1;
  for (let i = 0; i < 255; i++) {
    exp[i] = x;
    log[x] = i;
    x <<= 1;
    if (x & 0x100) x ^= 0x11D;
  }
  for (let i = 255; i < exp.length; i++) exp[i] = exp[i - 255];
  return { exp, log };
}

const GF = initGf();

function gfMul(a, b){
  return a && b ? GF.exp[GF.log[a] + GF.log[b]] : 0;
}

function rsGenerator(degree){
  let poly = [1];
  for (let i = 0; i < degree; i++) {
    const next = new Array(poly.length + 1).fill(0);
    for (let j = 0; j < poly.length; j++) {
      next[j] ^= poly[j];
      next[j + 1] ^= gfMul(poly[j], GF.exp[i]);
    }
    poly = next;
  }
  return poly.slice(1);
}

function rsRemainder(data, degree){
  const gen = rsGenerator(degree);
  const rem = new Array(degree).fill(0);
  for (const byte of data) {
    const factor = byte ^ rem.shift();
    rem.push(0);
    for (let i = 0; i < degree; i++) rem[i] ^= gfMul(gen[i], factor);
  }
  return rem;
}

function interleaveBlocks(data){
  const blocks = [];
  for (let i = 0; i < BLOCKS; i++) {
    const start = i * DATA_CODEWORDS_PER_BLOCK;
    const chunk = data.slice(start, start + DATA_CODEWORDS_PER_BLOCK);
    blocks.push({ data: chunk, ecc: rsRemainder(chunk, ECC_CODEWORDS_PER_BLOCK) });
  }

  const result = [];
  for (let i = 0; i < DATA_CODEWORDS_PER_BLOCK; i++) {
    for (const block of blocks) result.push(block.data[i]);
  }
  for (let i = 0; i < ECC_CODEWORDS_PER_BLOCK; i++) {
    for (const block of blocks) result.push(block.ecc[i]);
  }
  return result;
}

function blankMatrix(){
  return Array.from({ length: QR_SIZE }, () => Array(QR_SIZE).fill(false));
}

function cloneMatrix(matrix){
  return matrix.map(row => row.slice());
}

function createBaseMatrix(){
  const modules = blankMatrix();
  const isFunction = blankMatrix();

  function setFunc(row, col, dark){
    if (row < 0 || col < 0 || row >= QR_SIZE || col >= QR_SIZE) return;
    modules[row][col] = Boolean(dark);
    isFunction[row][col] = true;
  }

  function drawFinder(top, left){
    for (let dy = -1; dy <= 7; dy++) {
      for (let dx = -1; dx <= 7; dx++) {
        const row = top + dy;
        const col = left + dx;
        if (row < 0 || col < 0 || row >= QR_SIZE || col >= QR_SIZE) continue;
        const inPattern = dx >= 0 && dx <= 6 && dy >= 0 && dy <= 6;
        const dark = inPattern && (dx === 0 || dx === 6 || dy === 0 || dy === 6 || (dx >= 2 && dx <= 4 && dy >= 2 && dy <= 4));
        setFunc(row, col, dark);
      }
    }
  }

  function drawAlignment(centerRow, centerCol){
    for (let dy = -2; dy <= 2; dy++) {
      for (let dx = -2; dx <= 2; dx++) {
        const dist = Math.max(Math.abs(dx), Math.abs(dy));
        setFunc(centerRow + dy, centerCol + dx, dist !== 1);
      }
    }
  }

  drawFinder(0, 0);
  drawFinder(0, QR_SIZE - 7);
  drawFinder(QR_SIZE - 7, 0);
  drawAlignment(34, 34);

  for (let i = 8; i < QR_SIZE - 8; i++) {
    if (!isFunction[6][i]) setFunc(6, i, i % 2 === 0);
    if (!isFunction[i][6]) setFunc(i, 6, i % 2 === 0);
  }

  // Réservation des zones de format + module sombre fixe.
  for (let i = 0; i <= 8; i++) {
    if (i !== 6) {
      setFunc(i, 8, false);
      setFunc(8, i, false);
    }
  }
  for (let i = 0; i < 8; i++) setFunc(8, QR_SIZE - 1 - i, false);
  for (let i = 8; i < 15; i++) setFunc(QR_SIZE - 15 + i, 8, false);
  setFunc(QR_SIZE - 8, 8, true);

  return { modules, isFunction };
}

function maskBit(mask, row, col){
  switch(mask){
    case 0: return (row + col) % 2 === 0;
    case 1: return row % 2 === 0;
    case 2: return col % 3 === 0;
    case 3: return (row + col) % 3 === 0;
    case 4: return (Math.floor(row / 2) + Math.floor(col / 3)) % 2 === 0;
    case 5: return ((row * col) % 2 + (row * col) % 3) === 0;
    case 6: return (((row * col) % 2 + (row * col) % 3) % 2) === 0;
    case 7: return (((row + col) % 2 + (row * col) % 3) % 2) === 0;
    default: return false;
  }
}

function placeCodewords(baseModules, isFunction, codewords){
  const modules = cloneMatrix(baseModules);
  const isData = blankMatrix();
  const bits = [];
  codewords.forEach(byte => appendBits(bits, byte, 8));

  let bitIndex = 0;
  let upward = true;
  for (let col = QR_SIZE - 1; col >= 1; col -= 2) {
    if (col === 6) col--;
    for (let i = 0; i < QR_SIZE; i++) {
      const row = upward ? QR_SIZE - 1 - i : i;
      for (let offset = 0; offset < 2; offset++) {
        const c = col - offset;
        if (!isFunction[row][c]) {
          modules[row][c] = Boolean(bits[bitIndex++] || 0);
          isData[row][c] = true;
        }
      }
    }
    upward = !upward;
  }
  return { modules, isData };
}

function formatBits(mask){
  // Niveau d'erreur L = 01 dans le champ format QR.
  const data = (0b01 << 3) | mask;
  let bits = data << 10;
  for (let i = 14; i >= 10; i--) {
    if ((bits >>> i) & 1) bits ^= FORMAT_GENERATOR << (i - 10);
  }
  return ((data << 10) | bits) ^ FORMAT_XOR_MASK;
}

function bit(value, index){
  return ((value >>> index) & 1) !== 0;
}

function drawFormat(modules, mask){
  const bits = formatBits(mask);

  for (let i = 0; i <= 5; i++) modules[i][8] = bit(bits, i);
  modules[7][8] = bit(bits, 6);
  modules[8][8] = bit(bits, 7);
  modules[8][7] = bit(bits, 8);
  for (let i = 9; i < 15; i++) modules[8][14 - i] = bit(bits, i);

  for (let i = 0; i < 8; i++) modules[8][QR_SIZE - 1 - i] = bit(bits, i);
  for (let i = 8; i < 15; i++) modules[QR_SIZE - 15 + i][8] = bit(bits, i);
  modules[QR_SIZE - 8][8] = true;
}

function applyMask(modules, isData, mask){
  const out = cloneMatrix(modules);
  for (let row = 0; row < QR_SIZE; row++) {
    for (let col = 0; col < QR_SIZE; col++) {
      if (isData[row][col] && maskBit(mask, row, col)) out[row][col] = !out[row][col];
    }
  }
  drawFormat(out, mask);
  return out;
}

function penalty(matrix){
  let score = 0;
  const size = matrix.length;

  for (let row = 0; row < size; row++) {
    let runColor = matrix[row][0];
    let runLength = 1;
    for (let col = 1; col < size; col++) {
      if (matrix[row][col] === runColor) runLength++;
      else {
        if (runLength >= 5) score += 3 + (runLength - 5);
        runColor = matrix[row][col];
        runLength = 1;
      }
    }
    if (runLength >= 5) score += 3 + (runLength - 5);
  }

  for (let col = 0; col < size; col++) {
    let runColor = matrix[0][col];
    let runLength = 1;
    for (let row = 1; row < size; row++) {
      if (matrix[row][col] === runColor) runLength++;
      else {
        if (runLength >= 5) score += 3 + (runLength - 5);
        runColor = matrix[row][col];
        runLength = 1;
      }
    }
    if (runLength >= 5) score += 3 + (runLength - 5);
  }

  for (let row = 0; row < size - 1; row++) {
    for (let col = 0; col < size - 1; col++) {
      const color = matrix[row][col];
      if (color === matrix[row][col + 1] && color === matrix[row + 1][col] && color === matrix[row + 1][col + 1]) score += 3;
    }
  }

  const patternA = '10111010000';
  const patternB = '00001011101';
  for (let row = 0; row < size; row++) {
    const s = matrix[row].map(v => v ? '1' : '0').join('');
    for (let i = 0; i <= size - 11; i++) {
      const p = s.slice(i, i + 11);
      if (p === patternA || p === patternB) score += 40;
    }
  }
  for (let col = 0; col < size; col++) {
    let s = '';
    for (let row = 0; row < size; row++) s += matrix[row][col] ? '1' : '0';
    for (let i = 0; i <= size - 11; i++) {
      const p = s.slice(i, i + 11);
      if (p === patternA || p === patternB) score += 40;
    }
  }

  let dark = 0;
  for (const row of matrix) for (const v of row) if (v) dark++;
  const total = size * size;
  const k = Math.abs(Math.ceil((dark * 20) / total) - 10);
  score += k * 10;
  return score;
}

export function createQrMatrixForText(text){
  const data = createDataCodewords(text);
  const codewords = interleaveBlocks(data);
  const base = createBaseMatrix();
  const placed = placeCodewords(base.modules, base.isFunction, codewords);

  let best = null;
  for (let mask = 0; mask < 8; mask++) {
    const matrix = applyMask(placed.modules, placed.isData, mask);
    const score = penalty(matrix);
    if (!best || score < best.score) best = { matrix, score, mask };
  }
  return best.matrix;
}

export function makeEpcQrSvg(payloadText, { size = 224, title = 'QR code de virement SEPA' } = {}){
  const matrix = createQrMatrixForText(payloadText);
  const quiet = 4;
  const viewSize = matrix.length + quiet * 2;
  const parts = [];
  for (let row = 0; row < matrix.length; row++) {
    for (let col = 0; col < matrix.length; col++) {
      if (matrix[row][col]) parts.push(`M${col + quiet} ${row + quiet}h1v1h-1z`);
    }
  }
  return `<svg class="epc-qr-svg-v1" role="img" aria-label="${esc(title)}" width="${size}" height="${size}" viewBox="0 0 ${viewSize} ${viewSize}" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><path d="${parts.join(' ')}" fill="#111"/></svg>`;
}

export function epcDebugPayloadLength(payloadText){
  return toUtf8Bytes(payloadText).length;
}
