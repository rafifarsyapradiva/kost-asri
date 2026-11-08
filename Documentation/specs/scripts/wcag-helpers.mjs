/**
 * WCAG 2.1 contrast & CSS token helpers for Kost Asri Phase 2 verification.
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
