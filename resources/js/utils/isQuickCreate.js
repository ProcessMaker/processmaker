/**
 * Returns true if the current URL contains the `create=true` query parameter
 * @returns {boolean}
 */
export function isQuickCreate() {
  const searchParams = new URLSearchParams(window.location.search);
  return searchParams?.get("create") === "true";
}