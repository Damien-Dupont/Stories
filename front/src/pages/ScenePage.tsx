import { useScene } from "../hooks/useScene";
import { SceneContent } from "../components/SceneContent";

// appeler useScene avec un id
// si loading = true afficher <p>Chargement...</p>
// sinon afficher <SceneContent />p

export function ScenePage() {
  const { loading, scene } = useScene("scene-123");

  return loading === true ? (
    <p>Chargement...</p>
  ) : (
    <SceneContent
      title={scene?.title ?? ""}
      contentMarkdown={scene?.content_markdown ?? ""}
    />
  );
}
