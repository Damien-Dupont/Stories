import { renderHook, waitFor } from "@testing-library/react";
import { useScene } from "./useScene.ts";

describe("useScene", () => {
  it("has a loading state equals true", () => {
    const { result } = renderHook(() => useScene("scene-123"));
    expect(result.current.loading).toBe(true);
  });

  it("sets loading to false after fetch", async () => {
    // On remplace fetch par une fausse version
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

    const { result } = renderHook(() => useScene("scene-123"));

    // On attend que loading passe à false
    await waitFor(() => {
      expect(result.current.loading).toBe(false);
    });
  });
});
