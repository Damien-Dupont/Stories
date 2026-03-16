import { render, screen } from "@testing-library/react";
import { SceneEditPage } from "./SceneEditPage.tsx";
import { useSceneEdit } from "../hooks/useSceneEdit.ts";

vi.mock("../hooks/useSceneEdit");

describe("SceneEditPage", () => {
  it("displays actual title in an editable field", () => {
    vi.mocked(useSceneEdit).mockReturnValue({
      title: "La forêt",
      contentMarkdown: "# Début",
      loading: false,
      error: null,
    });

    render(<SceneEditPage />);
    expect(screen.getByDisplayValue("La forêt")).toBeInTheDocument();
  });
});
