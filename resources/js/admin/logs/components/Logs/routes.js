import { LogTable } from "./LogTable";

export default {};

/**
 * Check if a package is installed
 * @param {string} packageName - The package name to check
 * @returns {boolean}
 */
const isPackageInstalled = (packageName) => window.ProcessMaker?.packages?.includes(packageName);

/**
 * Check if email start event package is installed
 * @returns {boolean}
 */
export const hasEmailPackage = () => isPackageInstalled("package-email-start-event");

/**
 * Check if AI package is installed
 * @returns {boolean}
 */
export const hasAiPackage = () => isPackageInstalled("package-ai");

/**
 * Determine the default redirect path based on installed packages
 * @returns {string}
 */
const getDefaultRedirectPath = () => {
  if (hasEmailPackage()) {
    return "/email/errors";
  }
  if (hasAiPackage()) {
    return "/agents/design";
  }
  // Fallback - shouldn't happen if menu visibility is correct
  return "/email/errors";
};

export const routes = [
  {
    name: "logs.index",
    path: "/",
    beforeEnter: (to, from, next) => {
      next(getDefaultRedirectPath());
    },
  },
  // Email logs routes
  {
    name: "logs.email",
    path: "/email/:logType",
    component: LogTable,
    props(route) {
      return {
        category: "email",
        logType: route.params.logType,
      };
    },
    beforeEnter: (to, from, next) => {
      if (!hasEmailPackage()) {
        // Redirect to agents if email package not installed
        next(hasAiPackage() ? "/agents/design" : "/");
      } else {
        next();
      }
    },
  },
  // FlowGenie Agents logs routes
  {
    name: "logs.agents.redirect",
    path: "/agents",
    redirect: "/agents/design",
  },
  {
    name: "logs.agents",
    path: "/agents/:logType",
    component: LogTable,
    props(route) {
      return {
        category: "agents",
        logType: route.params.logType,
      };
    },
    beforeEnter: (to, from, next) => {
      if (!hasAiPackage()) {
        // Redirect to email if AI package not installed
        next(hasEmailPackage() ? "/email/errors" : "/");
      } else {
        next();
      }
    },
  },
];
