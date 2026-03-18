import { renderHook, waitFor } from "@testing-library/react";
import { useSceneEdit } from "./useSceneEdit.ts";

describe("useSceneEdit", () => {
  it("initializes with the scene title", async () => {
    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({
        status: "ok",
        data: {
          id: "scene-123",
          title: "La forêt",
          content_markdown: "# Début",
        },
      }),
    });
    const { result } = renderHook(() => useSceneEdit("scene-123"));
    await waitFor(() => {
      expect(result.current.title).toBe("La forêt");
    });
  });
});
