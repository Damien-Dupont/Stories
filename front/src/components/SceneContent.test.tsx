import { render, screen, fireEvent } from "@testing-library/react";
import { SceneContent } from "./SceneContent.tsx";

describe("SceneContent", () => {
  it("displays title of the scene", () => {
    render(<SceneContent title="La forêt sombre" contentMarkdown="# Début" />);
    expect(screen.getByText("La forêt sombre")).toBeInTheDocument();
  });

  it("displays Markdown content rendered in HTML", () => {
    render(
      <SceneContent title="La forêt" contentMarkdown="**texte en gras**" />,
    );
    expect(screen.getByText("texte en gras")).toBeInTheDocument();
  });

  it("displays a message when content is empty", () => {
    render(<SceneContent title="La forêt" contentMarkdown="" />);
    expect(screen.getByText("Aucun contenu disponible")).toBeInTheDocument();
  });

  it("displays list of next transitions", () => {
    const nextTransitions = [
      {
        transition_id: "1",
        transition_label: "Entrer dans la forêt",
        scene_id: "abc",
        transition_order: 1,
      },
      {
        transition_id: "2",
        transition_label: "Rebrousser chemin",
        scene_id: "def",
        transition_order: 2,
      },
    ];

    render(
      <SceneContent
        title="La forêt"
        contentMarkdown="# Début"
        nextTransitions={nextTransitions}
      />,
    );

    expect(screen.getByText("Entrer dans la forêt")).toBeInTheDocument();
    expect(screen.getByText("Rebrousser chemin")).toBeInTheDocument();
  });

  it("displays list of previous transitions", () => {
    const prevTransitions = [
      {
        transition_id: "1",
        transition_label: "Revenir à la clairière",
        scene_id: "abc",
        transition_order: 1,
      },
      {
        transition_id: "2",
        transition_label: "Retourner au village",
        scene_id: "def",
        transition_order: 2,
      },
    ];

    render(
      <SceneContent
        title="La forêt"
        contentMarkdown="# Début"
        prevTransitions={prevTransitions}
      />,
    );

    expect(screen.getByText("Revenir à la clairière")).toBeInTheDocument();
    expect(screen.getByText("Retourner au village")).toBeInTheDocument();
  });

  it("displays a message when there's no next transition", () => {
    render(
      <SceneContent
        title="La forêt"
        contentMarkdown="# Fin"
        nextTransitions={[]}
      />,
    );

    expect(screen.getByText("Fin de cette branche")).toBeInTheDocument();
  });

  it("calls onTransitionClick with the correct scene_id when a transition is clicked", () => {
    const onTransitionClick = vi.fn();
    const nextTransitions = [
      {
        transition_id: "1",
        transition_label: "Entrer dans la forêt",
        scene_id: "abc",
        transition_order: 1,
      },
      {
        transition_id: "2",
        transition_label: "Rebrousser chemin",
        scene_id: "def",
        transition_order: 2,
      },
    ];

    render(
      <SceneContent
        title="La forêt"
        contentMarkdown="# Début"
        nextTransitions={nextTransitions}
        onTransitionClick={onTransitionClick}
      />,
    );
    fireEvent.click(screen.getByText("Entrer dans la forêt"));
    expect(onTransitionClick).toHaveBeenCalledWith("abc");
  });
});
