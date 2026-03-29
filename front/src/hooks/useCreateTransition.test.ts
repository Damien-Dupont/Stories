import { renderHook, waitFor } from "@testing-library/react";
import { useCreateTransition } from "./useCreateTransition";

describe("useCreateTransition", () => {
  it("calls onTransitionCreate with proper scene_id when a scene is selected for transition", async () => {
    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({
        status: "ok",
        data: { sceneId: "lalala", sceneToLink: "bububu" },
      }),
    });
    const { result } = renderHook(() =>
      useCreateTransition("lalala", "bububu"),
    );

    await waitFor(() => {
      expect(result.current).toBe("ok");
    });
  });

  // it("", ()=>{})
});
