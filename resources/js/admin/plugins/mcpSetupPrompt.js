/**
 * Builds the "Coding Agent Setup" prompt shown in the Create Plugin dialog.
 *
 * Pure functions, no Vue or DOM access, so the logic can be checked on its own.
 *
 * The prompt must never contain secrets: the Git token typed in the dialog is only used by
 * ProcessMaker to clone private repositories and is deliberately not an input here.
 */

export const MCP_ENDPOINT_PATHS = [
  "/mcp/processes",
  "/mcp/screens",
  "/mcp/data-connectors",
  "/mcp/flow-genies",
];

export const MCP_PROFILE_VALUES = ["pm4", "migration"];

// Values are typed by the user and end up inside a prompt, so drop all whitespace
// (URLs, branches and usernames never contain any) to keep each value on one line.
const clean = (value) => String(value ?? "").replace(/\s+/g, "");

/**
 * Same rule as PluginManager::extractRepoName() in core: core installs the plugin as
 * storage/plugins/{repoName} and runs `{repoName}:install`, so the plugin folder created
 * from package-skeleton must carry exactly this name.
 */
export function extractRepoName(repoUrl) {
  const url = clean(repoUrl).replace(/(\.git|\/)$/, "");
  const match = url.match(/\/([^/]+)$/);
  return match ? match[1] : "";
}

/** True for an https URL that has a path (https://host/org/repo), the only kind core installs. */
export function isUsableRepoUrl(repoUrl) {
  try {
    const parsed = new URL(clean(repoUrl));
    return parsed.protocol === "https:" && parsed.pathname.replace(/\/+$/, "") !== "";
  } catch (e) {
    return false;
  }
}

/**
 * @param {object} options
 * @param {string} options.origin   ProcessMaker instance URL (window.location.origin)
 * @param {string} [options.repoUrl]
 * @param {string} [options.branch]
 * @param {string} [options.username]
 * @param {string} [options.profile] "pm4" or "migration"
 * @returns {string}
 */
export function buildMcpSetupPrompt({
  origin,
  repoUrl = "",
  branch = "",
  username = "",
  profile = "pm4",
}) {
  const base = clean(origin).replace(/\/+$/, "");
  const url = clean(repoUrl);
  const repoName = isUsableRepoUrl(url) ? extractRepoName(url) : "";

  const values = [
    ["PM_BASE_URL", base],
    ["PLUGIN_REPO", url || "<ask me for the repository URL>"],
    ["PLUGIN_NAME", repoName || "<repository name>"],
    ["PLUGIN_BRANCH", clean(branch) || "main"],
    ["GIT_USERNAME", clean(username) || "<my local git config>"],
    ["PROFILE", MCP_PROFILE_VALUES.includes(profile) ? profile : "pm4"],
  ];
  const width = Math.max(...values.map(([name]) => name.length));

  return [
    "Set up a ProcessMaker plugin workspace for this instance.",
    "",
    "Values (PLUGIN_NAME must equal the repository name):",
    ...values.map(([name, value]) => `  ${name.padEnd(width)} = ${value}`),
    "",
    "Before anything else, check `git --version` and `node -v` (Node >= 22; 24 is recommended).",
    "If one is missing, show me the install command for my OS and wait for my confirmation before running it.",
    "",
    "Then install the setup skill and follow it with the values above:",
    "  npx skills add processmaker/skills -y --skill pm4-plugin-setup",
    "",
    "The skill checks PHP and Composer, scaffolds the plugin from package-skeleton, builds it,",
    "commits it (including the public/ folder) and pushes it to PLUGIN_REPO with my local git credentials.",
    "",
    "MCP endpoints (authorize them with OAuth in my IDE; no token is needed):",
    ...MCP_ENDPOINT_PATHS.map((path) => `  - ${base}${path}`),
    "",
    "Rules:",
    "- Never ask me to paste a git token or an API token in this chat.",
    "  Tell me to add my ProcessMaker API token to .env as PM_SYNC_TOKEN myself.",
    "- Call get-mcp-guidance before other ProcessMaker MCP tools, then load only the matching skills.",
    "- Do not invent MCP tool names; discover them from the connected servers.",
    "- When you are done, summarize what passed and tell me to open Admin > Plugins, click Plugin,",
    "  enter PLUGIN_REPO and PLUGIN_BRANCH, and click Save.",
  ].join("\n");
}
