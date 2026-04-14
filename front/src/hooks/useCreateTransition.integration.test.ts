import { act, renderHook, waitFor } from "@testing-library/react";
import { useCreateTransition } from "./useCreateTransition";

// Ce test nécessite que le backend tourne sur localhost:8080
describe("useCreateTransition (integration)", () => {
  beforeEach(async () => {
    const res = await fetch("http://localhost:8080/transitions");
    const data = await res.json();
    const transition = data.data.find(
      (t: { scene_before_id: string; id: string }) =>
        t.scene_before_id === "1095d168-997e-427e-bf76-503a354bd834",
    );
    if (transition) {
      await fetch(`http://localhost:8080/transitions/${transition.id}`, {
        method: "DELETE",
      });
    }
  });
  it("fetches a real transition from the API", async () => {
    const { result } = renderHook(() =>
      useCreateTransition("1095d168-997e-427e-bf76-503a354bd834"),
    );

    await act(async () => {
      await result.current.createTransition(
        "09fb86b2-9ac4-4d4e-8a38-d29b0bbf748c",
        "VERS UN CHOIX",
      );
    });

    await waitFor(() => {
      expect(result.current.status).toBe("success");
    });
  });
});
