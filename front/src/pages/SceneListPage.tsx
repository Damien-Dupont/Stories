import { useSceneList } from "../hooks/useSceneList";

export function SceneListPage() {
  const { scenes, loading, error } = useSceneList();

  if (loading) return <p>Chargement...</p>;
  if (error) return <p>Yet unidentified error</p>;

  return (
    <ul>
      {scenes.map((s) => (
        <li key={s.id}>{s.title}</li>
      ))}
    </ul>
  );
}
