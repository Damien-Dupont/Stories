import { useState, useEffect } from "react";

interface Scene {
  id: string;
  title: string;
  global_order: number;
}

export function useSceneList() {
  const [scenes, setScenes] = useState<Scene[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const res = await fetch("http://localhost:8080/scenes");
        const json = await res.json();
        setScenes(json.data);
        setLoading(false);
      } catch {
        setError("Erreur au chargement");
        setLoading(false);
      }
    };
    fetchData();
  }, []);

  return { scenes, loading, error };
}
