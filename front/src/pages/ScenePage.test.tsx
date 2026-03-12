import { render, screen } from "@testing-library/react";
import { ScenePage } from "../pages/ScenePage";
import { useScene } from "../hooks/useScene.ts";

vi.mock("../hooks/useScene");

describe("ScenePage", () => {
  it("displays a loading pattern during fetch", () => {
    vi.mocked(useScene).mockReturnValue({ loading: true, scene: null });
    render(<ScenePage />);
    expect(screen.getByText("Chargement...")).toBeInTheDocument();
  });

  it("displays scene content after fetch", () => {
    vi.mocked(useScene).mockReturnValue({
      loading: false,
      scene: { id: "1", title: "Titre", content_markdown: "## coucou" },
    });
    render(<ScenePage />);
    expect(screen.getByText("coucou")).toBeInTheDocument();
  });

  it("displays an error message if fetch fails", () => {
    vi.mocked(useScene).mockReturnValue({
      loading: false,
      scene: null,
      error: "Erreur serveur",
    });
    render(<ScenePage />);
    expect(screen.getByText("Erreur serveur")).toBeInTheDocument();
  });
});
