import { renderHook, waitFor } from "@testing-library/react";
import { useScene } from "./useScene";

// Ce test nécessite que le backend tourne sur localhost:8080
describe("useScene (integration)", () => {
  it("fetches a real scene from the API", async () => {
    const { result } = renderHook(() =>
      useScene("30e60109-5fd9-454f-91b5-c58680a2ce6d"),
    );

    await waitFor(() => {
      expect(result.current.loading).toBe(false);
    });

    expect(result.current.scene).not.toBeNull();
    expect(result.current.scene?.title).toBeDefined();
  });
});
