import { useSceneEdit } from "../hooks/useSceneEdit";
import { useParams } from "react-router-dom";

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
  } = useSceneEdit(id ?? "");

  if (!id) return <p>Identifiant de scène manquant</p>;
  if (error) return <p>{error}</p>;
  if (loading) return <p>Chargement...</p>;

  return (
    <div>
      <button onClick={save}>Sauvegarder</button>
      <input value={title} onChange={(e) => setTitle(e.target.value)} />
      <textarea
        value={contentMarkdown}
        onChange={(e) => setContentMarkdown(e.target.value)}
      />
    </div>
  );
}
