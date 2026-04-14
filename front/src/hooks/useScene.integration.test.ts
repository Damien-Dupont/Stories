import { renderHook, waitFor } from "@testing-library/react";
import { useScene } from "./useScene";

// Ce test nécessite que le backend tourne sur localhost:8080
describe("useScene (integration)", () => {
  it("fetches a real scene from the API", async () => {
    const { result } = renderHook(() =>
      useScene("1095d168-997e-427e-bf76-503a354bd834"),
    );

    await waitFor(() => {
      expect(result.current.loading).toBe(false);
    });

    expect(result.current.scene).not.toBeNull();
    expect(result.current.scene?.title).toBeDefined();
  });
});
