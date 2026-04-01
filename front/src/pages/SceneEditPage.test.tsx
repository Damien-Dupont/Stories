import { fireEvent, render, screen } from "@testing-library/react";
import { SceneEditPage } from "./SceneEditPage.tsx";
import { useSceneEdit } from "../hooks/useSceneEdit.ts";
import { MemoryRouter, Route, Routes } from "react-router-dom";
import { useCreateTransition } from "../hooks/useCreateTransition.ts";
import { useSceneList } from "../hooks/useSceneList.ts";

vi.mock("../hooks/useSceneEdit");
vi.mock("../hooks/useSceneList");
vi.mock("../hooks/useCreateTransition");

//**
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
    save: vi.fn(),
    saveStatus: "idle",
    ...overrides,
  });
};

//**
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

//**
// * Helper mockUseSceneList
// */
const mockUseSceneList = () => {
  vi.mocked(useSceneList).mockReturnValue({
    scenes: [
      { id: "123", title: "le couloir" },
      { id: "132", title: "le balcon" },
    ],
    loading: false,
    error: null,
  });
};

//**
// * Helper mockUseCreateTransition
// */
const mockUseCreateTransition = () => {
  vi.mocked(useCreateTransition).mockReturnValue({
    createTransition: vi.fn(),
    error: null,
    status: "success",
  });
};

describe("SceneEditPage", () => {
  beforeEach(() => {
    mockUseSceneEdit();
    mockUseSceneList();
    mockUseCreateTransition();
  });
  it("displays actual title in an editable field", () => {
    renderEditPage();
    expect(screen.getByDisplayValue("La forêt")).toBeInTheDocument();
  });

  it("displays actual Markdown in an editable textarea", () => {
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

  it("displays a save button", () => {
    renderEditPage();
    expect(
      screen.getByRole("button", { name: "Sauvegarder" }),
    ).toBeInTheDocument();
  });

  it("initiate save function when 'save' button is clicked", () => {
    const save = vi.fn();
    mockUseSceneEdit({ save });
    renderEditPage();
    fireEvent.click(screen.getByText("Sauvegarder"));

    expect(save).toHaveBeenCalledTimes(1);
  });

  it("displays success message after saving", () => {
    mockUseSceneEdit({ saveStatus: "success" });
    renderEditPage();

    expect(screen.getByText("Sauvegarde réussie")).toBeInTheDocument();
  });

  it("displays error message if saving fails", () => {
    mockUseSceneEdit({ saveStatus: "error" });
    renderEditPage();

    expect(
      screen.getByText("Erreur lors de la sauvegarde"),
    ).toBeInTheDocument();
  });

  it("displays a rendered Markdown preview", () => {
    renderEditPage();
    expect(screen.getByRole("heading", { name: "Début" })).toBeInTheDocument();
  });

  it("displays two selectors, before and after markdown content", () => {
    mockUseSceneEdit({ contentMarkdown: "" });
    renderEditPage();

    const prevSelect = screen.getByPlaceholderText("Scène précédente");
    const nextSelect = screen.getByPlaceholderText("Scène suivante");

    const content = screen.getByText(/Aucun contenu disponible/i);

    // prevSelect is BEFORE content
    expect(
      prevSelect.compareDocumentPosition(content) &
        Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();

    // nextSelect is AFTER content
    expect(
      nextSelect.compareDocumentPosition(content) &
        Node.DOCUMENT_POSITION_PRECEDING,
    ).toBeTruthy();
  });

  it("populates TransitionForm with scenes from useSceneList", () => {
    renderEditPage();

    expect(screen.getAllByRole("option")).toHaveLength(2);
  });

  //  it("calls createTransition when a scene is selected in TransitionFrom", () => {})

  //  it("displays success message after transition is created", () => {})

  //   it("displays error message if transition creation fails", () => {})

  //  it("", () => {})
});
