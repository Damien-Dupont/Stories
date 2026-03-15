import { render, screen } from "@testing-library/react";
import { ScenePage } from "../pages/ScenePage";
import { useScene } from "../hooks/useScene.ts";
import { MemoryRouter, Route, Routes } from "react-router-dom";

vi.mock("../hooks/useScene");

describe("ScenePage", () => {
  it("displays a loading pattern during fetch", () => {
    vi.mocked(useScene).mockReturnValue({ loading: true, scene: null });
    render(
      <MemoryRouter initialEntries={["/scenes/scene-123"]}>
        <Routes>
          <Route path="/scenes/:id" element={<ScenePage />} />
        </Routes>
      </MemoryRouter>,
    );
    expect(screen.getByText("Chargement...")).toBeInTheDocument();
  });

  it("displays scene content after fetch", () => {
    vi.mocked(useScene).mockReturnValue({
      loading: false,
      scene: { id: "1", title: "Titre", content_markdown: "## coucou" },
    });
    render(
      <MemoryRouter initialEntries={["/scenes/scene-123"]}>
        <Routes>
          <Route path="/scenes/:id" element={<ScenePage />} />
        </Routes>
      </MemoryRouter>,
    );
    expect(screen.getByText("coucou")).toBeInTheDocument();
  });

  it("displays an error message if fetch fails", () => {
    vi.mocked(useScene).mockReturnValue({
      loading: false,
      scene: null,
      error: "Erreur serveur",
    });
    render(
      <MemoryRouter initialEntries={["/scenes/scene-123"]}>
        <Routes>
          <Route path="/scenes/:id" element={<ScenePage />} />
        </Routes>
      </MemoryRouter>,
    );
    expect(screen.getByText("Erreur serveur")).toBeInTheDocument();
  });
});
