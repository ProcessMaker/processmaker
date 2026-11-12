import {
  fetchSubProcessPreviewName,
  resolveSubProcessId,
  subProcessPreviewPayloadFromNode,
} from "./subprocessPreview";

describe("resolveSubProcessId", () => {
  it("parses processId from pm config JSON string", () => {
    const definition = {
      get(key) {
        if (key === "config") {
          return JSON.stringify({ processId: 42, calledElement: "ProcessId-42" });
        }
        return null;
      },
    };
    expect(resolveSubProcessId(definition)).toBe(42);
  });

  it("falls back to calledElement suffix", () => {
    const definition = {
      get(key) {
        if (key === "calledElement") {
          return "ProcessId-99";
        }
        return null;
      },
    };
    expect(resolveSubProcessId(definition)).toBe(99);
  });

  it("returns null when config is missing", () => {
    expect(resolveSubProcessId(null)).toBeNull();
    expect(resolveSubProcessId({ get: () => undefined })).toBeNull();
  });
});

describe("subProcessPreviewPayloadFromNode", () => {
  it("returns process id from node definition", () => {
    const node = {
      definition: {
        id: "node_1",
        get(key) {
          if (key === "config") {
            return JSON.stringify({ processId: 7 });
          }
          return null;
        },
      },
    };
    expect(subProcessPreviewPayloadFromNode(node)).toEqual({
      processId: 7,
      nodeId: "node_1",
    });
  });
});

describe("fetchSubProcessPreviewName", () => {
  beforeEach(() => {
    ProcessMaker.apiClient = {
      get: jest.fn().mockResolvedValue({ data: { name: "Child process" } }),
    };
  });

  it("returns process name from API", async () => {
    await expect(fetchSubProcessPreviewName(12)).resolves.toBe("Child process");
    expect(ProcessMaker.apiClient.get).toHaveBeenCalledWith("/processes/12");
  });
});
