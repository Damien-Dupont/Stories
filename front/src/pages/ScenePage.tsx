import { useScene } from "../hooks/useScene";
import { SceneContent } from "../components/SceneContent";
import { useParams, useNavigate } from "react-router-dom";

export function ScenePage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { loading, scene, error, nextTransitions, prevTransitions } = useScene(
    id ?? "",
  );

  if (!id) return <p>Identifiant de scène manquant</p>;
  if (error) return <p>{error}</p>;
  if (loading) return <p>Chargement...</p>;

  return (
    <SceneContent
      title={scene?.title ?? ""}
      contentMarkdown={scene?.content_markdown ?? ""}
      nextTransitions={nextTransitions ?? []}
      prevTransitions={prevTransitions ?? []}
      onTransitionClick={(sceneId) => navigate(`/scene/${sceneId}`)}
    />
  );
}
