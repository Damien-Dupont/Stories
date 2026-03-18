import { render, screen } from "@testing-library/react";
import { SceneEditPage } from "./SceneEditPage.tsx";
import { useSceneEdit } from "../hooks/useSceneEdit.ts";
import { MemoryRouter, Route, Routes } from "react-router-dom";

vi.mock("../hooks/useSceneEdit");

describe("SceneEditPage", () => {
  it("displays actual title in an editable field", () => {
    vi.mocked(useSceneEdit).mockReturnValue({
      title: "La forêt",
      contentMarkdown: "# Début",
      loading: false,
      error: null,
      setTitle: vi.fn(),
      setContentMarkdown: vi.fn(),
    });

    render(
      <MemoryRouter initialEntries={["/scenes/scene-123/edit"]}>
        <Routes>
          <Route path="/scenes/:id/edit" element={<SceneEditPage />} />
        </Routes>
      </MemoryRouter>,
    );
    expect(screen.getByDisplayValue("La forêt")).toBeInTheDocument();
  });
});
