import { act, renderHook, waitFor } from "@testing-library/react";
import { useCreateTransition } from "./useCreateTransition";

// Ce test nécessite que le backend tourne sur localhost:8080
describe("useCreateTransition (integration)", () => {
  it("fetches a real transition from the API", async () => {
    const { result } = renderHook(() =>
      useCreateTransition("30e60109-5fd9-454f-91b5-c58680a2ce6d"),
    );

    await act(async () => {
      await result.current.createTransition(
        "9fccf005-f23b-42c0-84b1-b851ca351ac1",
        "VERS UN CHOIX",
      );
    });

    await waitFor(() => {
      expect(result.current.status).toBe("success");
    });
  });
});
