import { ScreenTypes } from "../models/screens";

/**
 * Filter the screen types based on the task
 */
export function filterScreenType() {
  const screen = new URLSearchParams(window.location.search).get("screenType");
  return Object.entries(ScreenTypes).filter(([key, value]) => key === screen).reduce((acc, [key, value]) => {
    acc[key] = value;
    return acc;
  }, {});
}
