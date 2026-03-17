import { renderHook } from "@testing-library/react";
import { useSceneEdit } from "./useSceneEdit.ts";

describe("useSceneEdit", () => {
  it("initializes with the scene title", async () => {
    const { result } = renderHook(() => useSceneEdit("scene-123"));
    expect(result.current.title).toBe("La forêt");
  });
});
