import { render, screen } from "@testing-library/react";
import { ScenePage } from "../pages/ScenePage";
//import { useScene } from "../hooks/useScene.ts";

vi.mock("../hooks/useScene", () => ({
  useScene: () => ({ loading: true, scene: null, error: null }),
}));

describe("ScenePage", () => {
  it("displays a loading pattern during fetch", () => {
    render(<ScenePage />);
    expect(screen.getByText("Chargement...")).toBeInTheDocument();
  });

  //   it("sets loading to false after fetch", async () => {
  //     // On remplace fetch par une fausse version
  //     globalThis.fetch = vi.fn().mockResolvedValue({
  //       ok: true,
  //       json: async () => ({
  //         status: "ok",
  //         data: {
  //           id: "scene-123",
  //           title: "La forêt",
  //           content_markdown: "# Début",
  //         },
  //       }),
  //     });

  //     const { result } = renderHook(() => useScene("scene-123"));

  //     // On attend que loading passe à false
  //     await waitFor(() => {
  //       expect(result.current.loading).toBe(false);
  //     });
  //   });
});
