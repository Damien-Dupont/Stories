import { useSceneEdit } from "../hooks/useSceneEdit";
import { useParams } from "react-router-dom";
import { SceneContent } from "../components/SceneContent";
import { TransitionForm } from "../components/TransitionForm";
import { useSceneList } from "../hooks/useSceneList";

export function SceneEditPage() {
  const { id } = useParams<{ id: string }>();
  const {
    title,
    contentMarkdown,
    loading,
    error,
    setTitle,
    setContentMarkdown,
    save,
    saveStatus,
  } = useSceneEdit(id ?? "");

  const { scenes, loading: scenesLoading, error: scenesError } = useSceneList();

  if (!id) return <p>Identifiant de scène manquant</p>;
  if (error) return <p>{error}</p>;
  if (loading) return <p>Chargement...</p>;
  if (scenesError) return <p>{error}</p>;
  if (scenesLoading) return <p>Chargement...</p>;

  return (
    <div>
      {saveStatus === "success" && <p>Sauvegarde réussie</p>}
      {saveStatus === "error" && <p>Erreur lors de la sauvegarde</p>}
      <button onClick={save}>Sauvegarder</button>
      <input value={title} onChange={(e) => setTitle(e.target.value)} />
      <textarea
        value={contentMarkdown}
        onChange={(e) => setContentMarkdown(e.target.value)}
      />
      <div>
        <TransitionForm
          scenes={scenes}
          onTransitionCreate={(sceneId) =>
            console.log("transition vers", sceneId)
          }
          label="Scène précédente"
        />
        <SceneContent title="" contentMarkdown={contentMarkdown} />Ò
        <TransitionForm
          scenes={[]}
          onTransitionCreate={(sceneId) =>
            console.log("transition vers", sceneId)
          }
          label="Scène suivante"
        />
      </div>
    </div>
  );
}
// TODO: remplacer textarea par TipTap
