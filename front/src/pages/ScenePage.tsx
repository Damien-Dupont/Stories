import { useScene } from "../hooks/useScene";
import { SceneContent } from "../components/SceneContent";

export function ScenePage() {
  const { loading, scene, error } = useScene("scene-123");
  if (error) return <p>{error}</p>;
  if (loading) return <p>Chargement...</p>;
  return (
    <SceneContent
      title={scene?.title ?? ""}
      contentMarkdown={scene?.content_markdown ?? ""}
    />
  );
}
