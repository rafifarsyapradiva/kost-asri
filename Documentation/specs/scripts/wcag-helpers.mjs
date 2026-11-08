/**
 * WCAG 2.1 contrast, CSS token, & SEC-003 contract helpers for Kost Asri Phase 2 verification.
 */

export function relativeLuminance(hex) {
  const normalized = String(hex).trim().replace(/^#/, '');
  if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
    throw new Error(`Invalid 6-digit hex color: ${hex}`);
  }
  const channels = [0, 2, 4].map((offset) => {
    const srgb = parseInt(normalized.slice(offset, offset + 2), 16) / 255;
    return srgb <= 0.03928 ? srgb / 12.92 : ((srgb + 0.055) / 1.055) ** 2.4;
  });
  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
}

export function contrastRatio(fgHex, bgHex) {
  const l1 = relativeLuminance(fgHex);
  const l2 = relativeLuminance(bgHex);
  const lighter = Math.max(l1, l2);
  const darker = Math.min(l1, l2);
  return Number(((lighter + 0.05) / (darker + 0.05)).toFixed(2));
}

export function extractCssVariables(cssText) {
  const vars = {};
  const regex = /(--[a-zA-Z0-9-]+)\s*:\s*([^;]+);/g;
  for (const match of cssText.matchAll(regex)) {
    vars[match[1]] = match[2].trim();
  }
  return vars;
}

const FORBIDDEN_PUBLIC_KEYS = new Set([
  'user', 'user_id', 'tenant', 'tenant_name', 'occupant_name',
  'email', 'phone', 'nik', 'nik_hash', 'ktp_photo_path',
  'origin_address', 'institution_name',
  'guardian_name', 'guardian_phone', 'guardian_email', 'guardian_relation',
  'reservation_id', 'reservation_code', 'lease_id',
  'paid_until', 'billed_until', 'planned_checkout_date',
  'invoice', 'overdue', 'total_amount', 'dp_amount',
  'notes', 'maintenance_note', 'emergency_notes', 'checkout_notes',
]);

function validateNode(value, nodeSchema, pathLabel, errors) {
  if (!nodeSchema || typeof nodeSchema !== 'object') return;

  if (nodeSchema.type === 'object') {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) {
      errors.push(`${pathLabel}: expected object`);
      return;
    }
    const keys = Object.keys(value);
    for (const key of keys) {
      if (FORBIDDEN_PUBLIC_KEYS.has(key)) {
        errors.push(`${pathLabel}.${key}: forbidden PII/internal key exposed`);
      }
    }
    if (Array.isArray(nodeSchema.required)) {
      for (const reqKey of nodeSchema.required) {
        if (!(reqKey in value)) {
          errors.push(`${pathLabel}: missing required property "${reqKey}"`);
        }
      }
    }
    const props = nodeSchema.properties ?? {};
    if (nodeSchema.additionalProperties === false) {
      for (const key of keys) {
        if (!(key in props)) {
          errors.push(`${pathLabel}: disallowed additional property "${key}"`);
        }
      }
    }
    for (const [key, propSchema] of Object.entries(props)) {
      if (key in value) {
        validateNode(value[key], propSchema, `${pathLabel}.${key}`, errors);
      }
    }
    return;
  }

  if (nodeSchema.type === 'array') {
    if (!Array.isArray(value)) {
      errors.push(`${pathLabel}: expected array`);
      return;
    }
    if (typeof nodeSchema.minItems === 'number' && value.length < nodeSchema.minItems) {
      errors.push(`${pathLabel}: array length ${value.length} < minItems ${nodeSchema.minItems}`);
    }
    if (typeof nodeSchema.maxItems === 'number' && value.length > nodeSchema.maxItems) {
      errors.push(`${pathLabel}: array length ${value.length} > maxItems ${nodeSchema.maxItems}`);
    }
    if (nodeSchema.items) {
      value.forEach((item, idx) => validateNode(item, nodeSchema.items, `${pathLabel}[${idx}]`, errors));
    }
    return;
  }

  if (nodeSchema.type === 'string') {
    if (typeof value !== 'string') {
      errors.push(`${pathLabel}: expected string`);
      return;
    }
    if (nodeSchema.pattern && !new RegExp(nodeSchema.pattern).test(value)) {
      errors.push(`${pathLabel}: string "${value}" does not match pattern ${nodeSchema.pattern}`);
    }
  } else if (nodeSchema.type === 'integer') {
    if (!Number.isInteger(value)) {
      errors.push(`${pathLabel}: expected integer`);
      return;
    }
    if (typeof nodeSchema.minimum === 'number' && value < nodeSchema.minimum) {
      errors.push(`${pathLabel}: integer ${value} < minimum ${nodeSchema.minimum}`);
    }
  } else if (nodeSchema.type === 'boolean') {
    if (typeof value !== 'boolean') {
      errors.push(`${pathLabel}: expected boolean`);
      return;
    }
  }

  if (Array.isArray(nodeSchema.enum) && !nodeSchema.enum.includes(value)) {
    errors.push(`${pathLabel}: value "${value}" not in enum [${nodeSchema.enum.join(', ')}]`);
  }
}

export function validateAvailabilityContract(payload, schema) {
  const errors = [];
  validateNode(payload, schema, '$', errors);
  return {
    valid: errors.length === 0,
    errors,
  };
}
