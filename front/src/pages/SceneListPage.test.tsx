import { MemoryRouter, Route, Routes } from "react-router-dom";
import { useSceneList } from "../hooks/useSceneList";
import { SceneListPage } from "./SceneListPage";
import { render, screen } from "@testing-library/react";

vi.mock("../hooks/useSceneList");

//**
// * Helper mockSceneListPage
// */
const mockUseSceneList = () => {
  vi.mocked(useSceneList).mockReturnValue({
    scenes: [
      { id: "1", title: "La forêt", global_order: 1 },
      { id: "2", title: "Le chemin", global_order: 2 },
    ],
    loading: false,
    error: null,
  });
};

const renderListPage = () => {
  render(
    <MemoryRouter initialEntries={["/scenes"]}>
      <Routes>
        <Route path="/scenes/" element={<SceneListPage />} />
      </Routes>
    </MemoryRouter>,
  );
};

describe("SceneListPage", () => {
  it("displays list of titles of scenes", () => {
    mockUseSceneList();
    renderListPage();
    expect(screen.getByText(/La forêt/)).toBeInTheDocument(); // by regex !
  });

  it("displays a reading link on every scene", () => {
    mockUseSceneList();
    renderListPage();
    expect(screen.getAllByRole("link", { name: /Lire/i })).toHaveLength(2);
  });

  it("displays an edit link on every scene", () => {
    mockUseSceneList();
    renderListPage();
    expect(screen.getAllByRole("link", { name: /Editer/i })).toHaveLength(2);
  });

  it("displays a message if the list is empty", () => {
    vi.mocked(useSceneList).mockReturnValue({
      scenes: [],
      loading: false,
      error: null,
    });
    renderListPage();
    expect(screen.getByText(/Aucune scène pour le moment/)).toBeInTheDocument();
  });
});
