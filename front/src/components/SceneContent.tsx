import { marked } from "marked";

interface Transition {
  transition_id: string;
  transition_label: string;
  scene_id: string;
  transition_order: number;
}

interface SceneContentProps {
  title: string;
  contentMarkdown: string;
  nextTransitions?: Transition[];
  prevTransitions?: Transition[];
  onTransitionClick?: (sceneId: string) => void;
}

export function SceneContent({
  title,
  contentMarkdown,
  nextTransitions = [],
  prevTransitions = [],
  onTransitionClick,
}: SceneContentProps) {
  const renderedContent = marked.parse(contentMarkdown) as string;

  return (
    <div>
      {" "}
      {prevTransitions.length > 0 && (
        <ul>
          {[...prevTransitions]
            .sort((a, b) => a.transition_order - b.transition_order)
            .map((t) => (
              <li
                key={t.transition_id}
                onClick={() => onTransitionClick?.(t.scene_id)}
              >
                {t.transition_label}
              </li>
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
          {[...nextTransitions]
            .sort((a, b) => a.transition_order - b.transition_order)
            .map((t) => (
              <li
                key={t.transition_id}
                onClick={() => onTransitionClick?.(t.scene_id)}
              >
                {t.transition_label}
              </li>
            ))}
        </ul>
      )}
    </div>
  );
}
