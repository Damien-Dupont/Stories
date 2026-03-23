import { render, screen } from "@testing-library/react";
import { TransitionForm } from "./TransitionForm.tsx";

describe("TransitionForm", () => {
  it("displays a field to input transition label", () => {
    render(<TransitionForm scenes={[]} />);
    expect(
      screen.getByPlaceholderText("Label de la transition"),
    ).toBeInTheDocument();
  });

  it("displays a selector", () => {
    render(<TransitionForm scenes={[]} />);
    expect(screen.getByRole("combobox")).toBeInTheDocument();
  });

  it("displays scenes titles as options in the selector", () => {
    render(
      <TransitionForm
        scenes={[
          { id: "1", title: "La forêt" },
          { id: "2", title: "Le chemin" },
        ]}
      />,
    );
    expect(
      screen.getByRole("option", { name: "La forêt" }),
    ).toBeInTheDocument();
  });
});
