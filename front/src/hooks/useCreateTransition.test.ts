import { act, renderHook } from "@testing-library/react";
import { useCreateTransition } from "./useCreateTransition";

describe("useCreateTransition", () => {
  it("returns success status after calling createTransition", async () => {
    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({ status: "ok", data: {} }),
    });

    const { result } = renderHook(() => useCreateTransition("lalala"));

    await act(async () => {
      await result.current.createTransition("bububu", "Aller dans la forêt");
    });

    expect(result.current.status).toBe("success");
  });

  it("returns error status after calling createTransition return API error", async () => {
    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: false,
      json: async () => ({
        status: "error",
        error: "Erreur lors de la création de transition",
      }),
    });

    const { result } = renderHook(() => useCreateTransition("lalala"));

    await act(async () => {
      await result.current.createTransition("bububu", "Aller dans la forêt");
    });

    expect(result.current.status).toBe("error");
  });

  it("calls createTransition with the proper body", async () => {
    globalThis.fetch = vi.fn().mockResolvedValue({
      ok: false,
      json: async () => ({ status: "ok", data: {} }),
    });

    const { result } = renderHook(() => useCreateTransition("lalala"));

    await act(async () => {
      await result.current.createTransition("bububu", "Aller dans la forêt");
    });

    expect(globalThis.fetch).toHaveBeenCalledWith(
      `http://localhost:8080/transitions/lalala`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          forwardScene: "bububu",
          forwardLabel: "Aller dans la forêt",
        }),
      },
    );
  });
});
