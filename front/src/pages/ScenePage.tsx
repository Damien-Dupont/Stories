import { useScene } from "../hooks/useScene";
import { SceneContent } from "../components/SceneContent";
import { useParams } from "react-router-dom";

export function ScenePage() {
  const { id } = useParams<{ id: string }>();

  if (!id) return <p>Identifiant de scène manquant</p>;

  const { loading, scene, error, nextTransitions, prevTransitions } =
    useScene(id);

  if (error) return <p>{error}</p>;
  if (loading) return <p>Chargement...</p>;

  return (
    <SceneContent
      title={scene?.title ?? ""}
      contentMarkdown={scene?.content_markdown ?? ""}
      //  next={nextTransitions?.transition_label ?? ""}
    />
  );
}
