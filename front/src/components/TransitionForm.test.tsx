import { render, screen } from "@testing-library/react";
import { TransitionForm } from "./TransitionForm.tsx";
import { SceneEditPage } from "../pages/SceneEditPage.tsx";

describe("TransitionForm", () => {
  it("displays a field to input transition label", () => {
    render(<TransitionForm scenes={[]} label="test" />);
    expect(screen.getByPlaceholderText("test")).toBeInTheDocument();
  });

  it("displays a selector", () => {
    render(<TransitionForm scenes={[]} label="test" />);
    expect(screen.getByRole("combobox")).toBeInTheDocument();
  });

  it("displays scenes titles as options in the selector", () => {
    render(
      <TransitionForm
        scenes={[
          { id: "1", title: "La forêt" },
          { id: "2", title: "Le chemin" },
        ]}
        label="test"
      />,
    );
    expect(
      screen.getByRole("option", { name: "La forêt" }),
    ).toBeInTheDocument();
  });

  it("displays a second selector for next scenes", () => {
    render(<SceneEditPage />);

    const prevSelect = screen.getByLabelText("Transition précédente");
    const nextSelect = screen.getByLabelText("Transition suivante");

    const content = screen.getByText(/Aucun contenu disponible/i);

    // prevSelect est AVANT content
    expect(
      prevSelect.compareDocumentPosition(content) &
        Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();

    // nextSelect est APRÈS content
    expect(
      nextSelect.compareDocumentPosition(content) &
        Node.DOCUMENT_POSITION_PRECEDING,
    ).toBeTruthy();
  });

  // it("", ()=>{})
});
