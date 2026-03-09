import { marked } from "marked";

interface Transition {
  transition_id: string;
  transition_label: string;
  scene_id: string;
}

interface SceneContentProps {
  title: string;
  contentMarkdown: string;
  nextTransitions?: Transition[];
  prevTransitions?: Transition[];
}

export function SceneContent({
  title,
  contentMarkdown,
  nextTransitions = [],
  prevTransitions = [],
}: SceneContentProps) {
  const renderedContent = marked.parse(contentMarkdown) as string;

  return (
    <div>
      {" "}
      {prevTransitions.length > 0 && (
        <ul>
          {prevTransitions.map((t) => (
            <li key={t.transition_id}>{t.transition_label}</li>
          ))}
        </ul>
      )}
      <h1>{title}</h1>
      {contentMarkdown === "" ? (
        <p>Aucun contenu disponible</p>
      ) : (
        <div dangerouslySetInnerHTML={{ __html: renderedContent }} />
      )}
      {nextTransitions.length === 0 ? (
        <p>Fin de cette branche</p>
      ) : (
        <ul>
          {nextTransitions.map((t) => (
            <li key={t.transition_id}>{t.transition_label}</li>
          ))}
        </ul>
      )}
    </div>
  );
}
