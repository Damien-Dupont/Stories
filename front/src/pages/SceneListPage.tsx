import { Link } from "react-router-dom";
import { useSceneList } from "../hooks/useSceneList";

export function SceneListPage() {
  const { scenes, loading, error } = useSceneList();

  if (loading) return <p>Chargement...</p>;
  if (error) return <p>Yet unidentified error</p>;
  if (scenes.length == 0) return <p>Aucune scène pour le moment</p>;

  return (
    <ul>
      {scenes.map((s) => (
        <li key={s.id}>
          {s.title}
          {" => "}
          <Link to={`/scenes/${s.id}`}>Lire</Link>
          {" / "}
          <Link to={`/scenes/${s.id}/edit`}>Editer</Link>
        </li>
      ))}
    </ul>
  );
}
