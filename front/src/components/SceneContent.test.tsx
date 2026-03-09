import { render, screen } from "@testing-library/react";
import { SceneContent } from "./SceneContent.tsx";

describe("SceneContent", () => {
  it("affiche le titre de la scène", () => {
    render(<SceneContent title="La forêt sombre" contentMarkdown="# Début" />);
    expect(screen.getByText("La forêt sombre")).toBeInTheDocument();
  });

  it("affiche le contenu Markdown rendu en HTML", () => {
    render(
      <SceneContent title="La forêt" contentMarkdown="**texte en gras**" />,
    );
    expect(screen.getByText("texte en gras")).toBeInTheDocument();
  });

  it("affiche un message quand le contenu est vide", () => {
    render(<SceneContent title="La forêt" contentMarkdown="" />);
    expect(screen.getByText("Aucun contenu disponible")).toBeInTheDocument();
  });

  it("affiche la liste des chemins suivants", () => {
    const nextTransitions = [
      {
        transition_id: "1",
        transition_label: "Entrer dans la forêt",
        scene_id: "abc",
      },
      {
        transition_id: "2",
        transition_label: "Rebrousser chemin",
        scene_id: "def",
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
  it("affiche la liste des chemins précédents", () => {
    const prevTransitions = [
      {
        transition_id: "1",
        transition_label: "Revenir à la clairière",
        scene_id: "abc",
      },
      {
        transition_id: "2",
        transition_label: "Retourner au village",
        scene_id: "def",
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

  it("affiche un message quand il n'y a aucun chemin suivant", () => {
    render(
      <SceneContent
        title="La forêt"
        contentMarkdown="# Fin"
        nextTransitions={[]}
      />,
    );

    expect(screen.getByText("Fin de cette branche")).toBeInTheDocument();
  });
});
