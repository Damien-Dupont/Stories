import { render, screen } from "@testing-library/react";
import { TransitionForm } from "./TransitionForm.tsx";

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

  // it("", ()=>{})
});
