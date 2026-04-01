export const capitalizeWords = (text) => {
  if (!text) return '';

  return text
    .toLowerCase()
    .trim()
    .replace(/\s+/g, ' ')
    .replace(/\b\w/g, char => char.toUpperCase());
}
