import { fireEvent, render, screen } from "@testing-library/react";
import { SceneEditPage } from "./SceneEditPage.tsx";
import { useSceneEdit } from "../hooks/useSceneEdit.ts";
import { MemoryRouter, Route, Routes } from "react-router-dom";

vi.mock("../hooks/useSceneEdit");

//**
// *
// * Helper mockUseSceneEdit
// */
const mockUseSceneEdit = (overrides = {}) => {
  vi.mocked(useSceneEdit).mockReturnValue({
    title: "La forêt",
    contentMarkdown: "# Début",
    loading: false,
    error: null,
    setTitle: vi.fn(),
    setContentMarkdown: vi.fn(),
    ...overrides,
  });
};

//**
// *
// * Helper renderEditPage
// */
const renderEditPage = () => {
  render(
    <MemoryRouter initialEntries={["/scenes/scene-123/edit"]}>
      <Routes>
        <Route path="/scenes/:id/edit" element={<SceneEditPage />} />
      </Routes>
    </MemoryRouter>,
  );
};

describe("SceneEditPage", () => {
  it("displays actual title in an editable field", () => {
    mockUseSceneEdit();
    renderEditPage();
    expect(screen.getByDisplayValue("La forêt")).toBeInTheDocument();
  });

  it("displays actual Markdown in an editable textarea", () => {
    mockUseSceneEdit();

    renderEditPage();
    expect(screen.getByDisplayValue("# Début")).toBeInTheDocument();
  });

  it("updates title when user types", () => {
    const setTitle = vi.fn();
    mockUseSceneEdit({ setTitle });
    renderEditPage();
    fireEvent.change(screen.getByDisplayValue("La forêt"), {
      target: { value: "Nouveau titre" },
    });

    expect(setTitle).toHaveBeenCalledWith("Nouveau titre");
  });
});
