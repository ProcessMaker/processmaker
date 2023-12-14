/**
 * Returns true if the current URL contains the `create=true` query parameter
 * @returns {boolean}
 */
export function isQuickCreate() {
  const searchParams = new URLSearchParams(window.location.search);
  return searchParams?.get("create") === "true";
}

export function screenSelectId() {
  const searchParams = new URLSearchParams(window.location.search);
  const selectId = searchParams?.get("screenSelectId");
  searchParams.delete("screenSelectId");
  window.history.replaceState(null, "", `?${searchParams.toString()}`);
  return selectId;
}
