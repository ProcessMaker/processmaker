export function strLimit(value, size) {
  if (value == null || value === "") {
    return "";
  }
  const str = String(value);
  if (str.length <= size) {
    return str;
  }
  return `${str.slice(0, size)}...`;
}
