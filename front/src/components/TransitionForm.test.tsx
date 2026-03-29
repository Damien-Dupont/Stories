import { fireEvent, render, screen } from "@testing-library/react";
import { TransitionForm } from "./TransitionForm.tsx";

const onTransitionCreate = vi.fn();

describe("TransitionForm", () => {
  it("displays a field to input transition label", () => {
    render(
      <TransitionForm
        scenes={[]}
        label="test"
        onTransitionCreate={onTransitionCreate}
      />,
    );
    expect(screen.getByPlaceholderText("test")).toBeInTheDocument();
  });

  it("displays a selector", () => {
    render(
      <TransitionForm
        scenes={[]}
        label="test"
        onTransitionCreate={onTransitionCreate}
      />,
    );
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
        onTransitionCreate={onTransitionCreate}
      />,
    );
    expect(
      screen.getByRole("option", { name: "La forêt" }),
    ).toBeInTheDocument();
  });

  it("calls onTransitionCreate when a scene is selected for transition", () => {
    render(
      <TransitionForm
        scenes={[{ id: "1", title: "La forêt" }]}
        label="test"
        onTransitionCreate={onTransitionCreate}
      />,
    );

    // simuler la sélection dans le select
    fireEvent.change(screen.getByRole("combobox"), {
      target: { value: "1" },
    });

    expect(onTransitionCreate).toHaveBeenCalledWith("1");
  });
});
