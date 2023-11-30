import { ScreenTypes } from "../models/screens";

/**
 * Filter the screen types based on the task
 * @return {?Object};
 */
export function filterScreenType() {
  const screen = new URLSearchParams(window.location.search).get("screenType")?.split(",");
  if (!screen) return;
  return Object.fromEntries(Object.entries(ScreenTypes).filter(([key]) => screen.includes(key)));
}
